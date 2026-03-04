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
     * Human-readable platform label
     */
    private function platformLabel(string $platform): string
    {
        return match ($platform) {
            'android' => 'Android',
            'ios'     => 'iOS',
            'debug'   => 'Debug',
            default   => ucfirst($platform),
        };
    }

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
    //  CLIENT API (1) — app calls this with platform + version
    // ─────────────────────────────────────────────

    /**
     * POST config JSON for a given platform and version
     * POST /api/object-ai/config
     * Body: { "key": "xxx", "platform": "android", "version": "2.12.2" }
     *
     * Body params:
     *   platform  — android | ios | debug
     *   version   — semver string, e.g. 2.12.2
     */
    public function getFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,debug',
            'file_version'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform         = $request->input('platform');
        $requestedVersion = $request->input('file_version');

        $matchedVersion = $this->findBestVersion($platform, $requestedVersion);

        if (!$matchedVersion) {
            return $this->error("No JSON config found for {$platform} version {$requestedVersion}", 404);
        }

        $data = $this->loadJsonFile($platform, $matchedVersion);

        if ($data === null) {
            return $this->error('Config file could not be read', 500);
        }

        $label = $this->platformLabel($platform);
        if ($matchedVersion === $requestedVersion) {
            $msg = "Returning {$label} config for version {$matchedVersion}.";
        } else {
            $msg = "Requested {$requestedVersion} not available; returning {$label} config for nearest version {$matchedVersion}.";
        }

        return $this->success([
            'platform'          => $platform,
            'matched_version'   => $matchedVersion,
            'requested_version' => $requestedVersion,
            'config'            => $data,
        ], $msg);
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
        // optional platform filter -- must be one of the known platforms
        $platform = $request->input('platform');
        if ($platform && !in_array($platform, ['android','ios','debug'])) {
            return $this->error('Invalid platform, must be android, ios or debug', 400);
        }

        $platforms = $platform ? [$platform] : ['android', 'ios', 'debug'];
        $result = [];

        foreach ($platforms as $p) {
            $versions = $this->getVersionsForPlatform($p);
            foreach ($versions as $v) {
                $filePath = $this->basePath($p) . "/{$v}.json";
                $result[] = [
                    'platform'   => $p,
                    'file_version'    => $v,
                    'file_size'  => file_exists($filePath) ? filesize($filePath) : 0,
                    'updated_at' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null,
                ];
            }
        }

        $count = count($result);
        if ($platform) {
            $label = $this->platformLabel($platform);
            $msg = $count
                ? "Found {$count} {$label} file" . ($count === 1 ? '' : 's')
                : "No {$label} files found";
        } else {
            $msg = $count
                ? "Found {$count} files across all platforms"
                : 'No versioned files available';
        }

        return $this->success($result, $msg);
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
            'file_version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
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

        $label = $this->platformLabel($platform);

        return $this->success([
            'platform' => $platform,
            'file_version'  => $version,
        ], "JSON file for {$label} version {$version} has been created successfully.");
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
            'file_version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
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

        $label = $this->platformLabel($platform);

        return $this->success([
            'platform' => $platform,
            'file_version'  => $version,
        ], "JSON file for {$label} version {$version} has been updated successfully.");
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
            'file_version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
        $destPath = $this->basePath($platform) . "/{$version}.json";

        if (!file_exists($destPath)) {
            return $this->error("Version {$version} for {$platform} not found", 404);
        }

        unlink($destPath);

        Log::info("Versioned JSON deleted", ['platform' => $platform, 'version' => $version]);

        $label = $this->platformLabel($platform);

        return $this->success(null, "JSON file for {$label} version {$version} has been deleted successfully.");
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
