<?php

namespace App\Http\Controllers\ObjectAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class VersionedJsonController extends Controller
{
    /**
     * Base directory for versioned JSON files
     */
    private function basePath(string $platform = ''): string
    {
        $path = storage_path('app/versioned');
        return $platform ? "{$path}/{$platform}" : $path;
    }

    /**
     * Get all version numbers for a platform by scanning the folder
     */
    private function getVersionsForPlatform(string $platform): array
    {
        $dir = $this->basePath($platform);

        if (!is_dir($dir)) {
            return [];
        }

        $files = glob("{$dir}/*.json");
        $versions = [];

        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME); // e.g. "2.12.2"
            if (preg_match('/^\d+\.\d+\.\d+$/', $filename)) {
                $versions[] = $filename;
            }
        }

        // Sort versions ascending
        usort($versions, 'version_compare');

        return $versions;
    }

    /**
     * Find best matching version (exact or nearest lower)
     * e.g. app asks for 2.13.0, only 2.12.2 exists → returns 2.12.2
     */
    private function findBestVersion(string $platform, string $requestedVersion): ?string
    {
        $versions = $this->getVersionsForPlatform($platform);

        if (empty($versions)) {
            return null;
        }

        // Exact match
        if (in_array($requestedVersion, $versions)) {
            return $requestedVersion;
        }

        // Fallback: highest version <= requested
        $best = null;
        foreach ($versions as $v) {
            if (version_compare($v, $requestedVersion, '<=')) {
                $best = $v;
            }
        }

        return $best;
    }

    /**
     * Load JSON content from a versioned file
     */
    private function loadJsonFile(string $platform, string $version): ?array
    {
        $filePath = $this->basePath($platform) . "/{$version}.json";

        if (!file_exists($filePath)) {
            return null;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }


    // ─────────────────────────────────────────────
    //  CLIENT APIs (3) — app calls these with version
    // ─────────────────────────────────────────────

    /**
     * GET JSON for Android app by version
     * POST /api/object-ai/android
     * Body: { "key": "xxx", "version": "2.12.2" }
     */
    public function getAndroidJson(Request $request)
    {
        return $this->getJsonByPlatform($request, 'android');
    }

    /**
     * GET JSON for iOS app by version
     * POST /api/object-ai/ios
     * Body: { "key": "xxx", "version": "2.12.2" }
     */
    public function getIosJson(Request $request)
    {
        return $this->getJsonByPlatform($request, 'ios');
    }

    /**
     * GET JSON for Debug mode by version
     * POST /api/object-ai/debug
     * Body: { "key": "xxx", "version": "2.12.2" }
     */
    public function getDebugJson(Request $request)
    {
        return $this->getJsonByPlatform($request, 'debug');
    }

    /**
     * Common logic: find best version match and return JSON
     */
    private function getJsonByPlatform(Request $request, string $platform)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('version is required (e.g. 2.12.2)', 400);
        }

        $requestedVersion = $request->input('version');

        $matchedVersion = $this->findBestVersion($platform, $requestedVersion);

        if (!$matchedVersion) {
            return $this->error("No JSON config found for {$platform} version {$requestedVersion}", 404);
        }

        $data = $this->loadJsonFile($platform, $matchedVersion);

        if ($data === null) {
            return $this->error('Config file could not be read', 500);
        }

        return $this->success([
            'matched_version' => $matchedVersion,
            'requested_version' => $requestedVersion,
            'config' => $data,
        ]);
    }


    // ─────────────────────────────────────────────
    //  ADMIN APIs (4) — manage versioned JSON files
    // ─────────────────────────────────────────────

    /**
     * LIST all versions (optionally filter by platform)
     * POST /api/object-ai/versions/list
     * Body: { "key": "xxx" }
     * Optional: { "key": "xxx", "platform": "android" }
     */
    public function listVersions(Request $request)
    {
        $platform = $request->input('platform');
        $platforms = $platform ? [$platform] : ['android', 'ios', 'debug'];

        $result = [];

        foreach ($platforms as $p) {
            $versions = $this->getVersionsForPlatform($p);
            foreach ($versions as $v) {
                $filePath = $this->basePath($p) . "/{$v}.json";
                $result[] = [
                    'platform'   => $p,
                    'version'    => $v,
                    'file_size'  => file_exists($filePath) ? filesize($filePath) : 0,
                    'updated_at' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null,
                ];
            }
        }

        return $this->success($result);
    }

    /**
     * CREATE a new versioned JSON file
     * POST /api/object-ai/versions/create
     * Body (form-data): key, platform (android|ios|debug), version (2.12.2), file (.json)
     */
    public function createVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,debug',
            'version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('version');
        $destPath = $this->basePath($platform) . "/{$version}.json";

        // Check if already exists
        if (file_exists($destPath)) {
            return $this->error("Version {$version} for {$platform} already exists. Use update instead.", 409);
        }

        // Validate JSON content
        $file = $request->file('file');
        $content = file_get_contents($file);

        if (json_decode($content, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Invalid JSON file: ' . json_last_error_msg(), 400);
        }

        // Ensure directory exists
        if (!is_dir($this->basePath($platform))) {
            mkdir($this->basePath($platform), 0755, true);
        }

        // Save file
        file_put_contents($destPath, $content);

        // Upload to GitHub
        $this->uploadToGitHub($content, "versioned/{$platform}/{$version}.json", "Add {$platform} v{$version} config");

        Log::info("Versioned JSON created", ['platform' => $platform, 'version' => $version]);

        return $this->success([
            'platform' => $platform,
            'version'  => $version,
        ], 'Version created successfully');
    }

    /**
     * UPDATE an existing versioned JSON file
     * POST /api/object-ai/versions/update
     * Body (form-data): key, platform, version, file (.json)
     */
    public function updateVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,debug',
            'version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('version');
        $destPath = $this->basePath($platform) . "/{$version}.json";

        // Check exists
        if (!file_exists($destPath)) {
            return $this->error("Version {$version} for {$platform} not found. Use create first.", 404);
        }

        // Validate JSON content
        $file = $request->file('file');
        $content = file_get_contents($file);

        if (json_decode($content, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Invalid JSON file: ' . json_last_error_msg(), 400);
        }

        // Overwrite file
        file_put_contents($destPath, $content);

        // Upload to GitHub
        $this->uploadToGitHub($content, "versioned/{$platform}/{$version}.json", "Update {$platform} v{$version} config");

        Log::info("Versioned JSON updated", ['platform' => $platform, 'version' => $version]);

        return $this->success([
            'platform' => $platform,
            'version'  => $version,
        ], 'Version updated successfully');
    }

    /**
     * DELETE a versioned JSON file
     * POST /api/object-ai/versions/delete
     * Body: { "key": "xxx", "platform": "android", "version": "2.12.2" }
     */
    public function deleteVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,debug',
            'version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('version');
        $destPath = $this->basePath($platform) . "/{$version}.json";

        if (!file_exists($destPath)) {
            return $this->error("Version {$version} for {$platform} not found", 404);
        }

        unlink($destPath);

        Log::info("Versioned JSON deleted", ['platform' => $platform, 'version' => $version]);

        return $this->success(null, "Version {$version} for {$platform} deleted successfully");
    }


    // ─────────────────────────────────────────────
    //  GitHub Helper
    // ─────────────────────────────────────────────

    private function uploadToGitHub(string $content, string $relativePath, string $commitMessage): void
    {
        $owner  = env('GITHUB_REPO_OWNER');
        $repo   = env('GITHUB_REPO_NAME');
        $branch = env('GITHUB_BRANCH', 'main');
        $token  = env('GITHUB_TOKEN');

        if (!$owner || !$repo || !$token) {
            Log::warning("GitHub config missing, skipping upload");
            return;
        }

        try {
            $githubPath = "storage/app/{$relativePath}";
            $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$githubPath}";
            $contentBase64 = base64_encode($content);

            // Get existing SHA if file exists on GitHub
            $sha = null;
            $shaResponse = Http::withToken($token)->get($url, ['ref' => $branch]);
            if ($shaResponse->ok()) {
                $sha = $shaResponse->json()['sha'];
            }

            $payload = [
                'message' => $commitMessage,
                'content' => $contentBase64,
                'branch'  => $branch,
            ];

            if ($sha) {
                $payload['sha'] = $sha;
            }

            $response = Http::withToken($token)->put($url, $payload);

            if ($response->failed()) {
                Log::error("GitHub upload failed", ['status' => $response->status(), 'body' => $response->body()]);
            } else {
                Log::info("GitHub upload successful", ['path' => $githubPath]);
            }
        } catch (\Exception $e) {
            Log::error("GitHub upload exception", ['error' => $e->getMessage()]);
        }
    }
}
