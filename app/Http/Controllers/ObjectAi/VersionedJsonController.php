<?php

namespace App\Http\Controllers\ObjectAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class VersionedJsonController extends Controller
{
    private const PLATFORMS = ['android', 'ios', 'debug'];

    private const LEGACY_VERSIONED_FOLDER = 'versioned';

    private const DEFAULT_LOOKUP_FOLDER = 'versioned';

    /**
      * Normalize folder input
     */
    private function normalizeFolderName(?string $folder): string
    {
        $folder = trim((string) $folder);
          return strtolower($folder);
    }

    /**
     * Resolve folder for read/lookups with default fallback
     */
    private function resolveLookupFolder(?string $folder): string
    {
        $normalized = $this->normalizeFolderName($folder);
        return $normalized !== '' ? $normalized : self::DEFAULT_LOOKUP_FOLDER;
    }

    /**
     * Validate folder name format
     */
    private function isValidFolderName(string $folder): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{0,49}$/', $folder);
    }

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

    private function storageRootPath(): string
    {
        return storage_path('app');
    }

    /**
     * Absolute path of a managed folder inside storage/app
     */
    private function folderPath(string $folder = ''): string
    {
        $path = $this->storageRootPath();
        return $folder !== '' ? "{$path}/{$folder}" : $path;
    }

    private function isManagedFolder(string $folder): bool
    {
        if ($folder === self::LEGACY_VERSIONED_FOLDER) {
            return true;
        }

        foreach (self::PLATFORMS as $platform) {
            if (is_dir($this->folderPath($folder) . "/{$platform}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Base directory for versioned JSON files
     */
    private function basePath(string $platform = '', ?string $folder = null): string
    {
        $folder = $this->normalizeFolderName($folder);
        $path = $this->folderPath($folder);
        return $platform ? "{$path}/{$platform}" : $path;
    }

    /**
     * Get all managed folder names from storage/app
     */
    private function getFolderNames(): array
    {
        $base = $this->folderPath();
        $folders = [];

        if (is_dir($base)) {
            $items = scandir($base) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $full = "{$base}/{$item}";
                if (!is_dir($full)) {
                    continue;
                }

                $normalized = strtolower($item);
                if (!$this->isManagedFolder($normalized)) {
                    continue;
                }

                $folders[] = $normalized;
            }
        }

        sort($folders);

        return $folders;
    }

    /**
     * Get all version numbers for a platform by scanning the folder
     */
    private function getVersionsForPlatform(string $platform, ?string $folder = null): array
    {
        $folder = $this->normalizeFolderName($folder);
        $dirs = [$this->basePath($platform, $folder)];

        $versions = [];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob("{$dir}/*.json") ?: [];

            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME); // e.g. "2.12.2"
                if (preg_match('/^\d+\.\d+\.\d+$/', $filename)) {
                    $versions[$filename] = true;
                }
            }
        }

        $versions = array_keys($versions);

        // Sort versions ascending
        usort($versions, 'version_compare');

        return $versions;
    }

    /**
     * Find best matching version (exact or nearest lower)
     * e.g. app asks for 2.13.0, only 2.12.2 exists → returns 2.12.2
     */
    private function findBestVersion(string $platform, string $requestedVersion, ?string $folder = null): ?string
    {
        $versions = $this->getVersionsForPlatform($platform, $folder);

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
    private function resolveVersionFilePath(string $platform, string $version, ?string $folder = null): string
    {
        $folder = $this->normalizeFolderName($folder);
        $newPath = $this->basePath($platform, $folder) . "/{$version}.json";

        return $newPath;
    }

    /**
     * Load JSON content from a versioned file
     */
    private function loadJsonFile(string $platform, string $version, ?string $folder = null): ?array
    {
        $filePath = $this->resolveVersionFilePath($platform, $version, $folder);

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
            'folder_name' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform         = $request->input('platform');
        $requestedVersion = $request->input('file_version');
        $folder = $this->resolveLookupFolder($request->input('folder_name'));

        $matchedVersion = $this->findBestVersion($platform, $requestedVersion, $folder);

        if (!$matchedVersion) {
            return $this->error("No JSON config found for folder {$folder}, {$platform} version {$requestedVersion}", 404);
        }

        $data = $this->loadJsonFile($platform, $matchedVersion, $folder);

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
            'folder_name'       => $folder,
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

        $folder = $request->input('folder_name');
        if ($folder) {
            $folder = $this->normalizeFolderName($folder);
            if (!$this->isValidFolderName($folder)) {
                return $this->error('Invalid folder_name format', 400);
            }
            if (!is_dir($this->folderPath($folder))) {
                return $this->error("Folder {$folder} not found", 404);
            }
        }

        $platforms = $platform ? [$platform] : ['android', 'ios', 'debug'];
        $folders = $folder ? [$folder] : $this->getFolderNames();
        $result = [];

        foreach ($folders as $folderName) {
            foreach ($platforms as $p) {
                $versions = $this->getVersionsForPlatform($p, $folderName);
                foreach ($versions as $v) {
                    $filePath = $this->resolveVersionFilePath($p, $v, $folderName);
                    $result[] = [
                        'folder_name' => $folderName,
                        'platform'   => $p,
                        'file_version'    => $v,
                        'file_size'  => file_exists($filePath) ? filesize($filePath) : 0,
                        'updated_at' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null,
                    ];
                }
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
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
        $folder = $this->normalizeFolderName($request->input('folder_name'));
        $destDir = $this->basePath($platform, $folder);
        $destPath = "{$destDir}/{$version}.json";

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
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Save file
        file_put_contents($destPath, $content);

        // Upload to GitHub
        $this->uploadToGitHub($content, "{$folder}/{$platform}/{$version}.json", "Add {$folder}/{$platform} v{$version} config");

        Log::info("Versioned JSON created", ['folder_name' => $folder, 'platform' => $platform, 'version' => $version]);

        $label = $this->platformLabel($platform);

        return $this->success([
            'folder_name' => $folder,
            'platform' => $platform,
            'file_version'  => $version,
        ], "JSON file for folder {$folder}, {$label} version {$version} has been created successfully.");
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
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
            'file'     => 'required|file|extensions:json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
        $folder = $this->normalizeFolderName($request->input('folder_name'));
        $destPath = $this->resolveVersionFilePath($platform, $version, $folder);

        // Check exists
        if (!file_exists($destPath)) {
            return $this->error("Version {$version} for folder {$folder}, {$platform} not found. Use create first.", 404);
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
        $this->uploadToGitHub($content, "{$folder}/{$platform}/{$version}.json", "Update {$folder}/{$platform} v{$version} config");

        Log::info("Versioned JSON updated", ['folder_name' => $folder, 'platform' => $platform, 'version' => $version]);

        $label = $this->platformLabel($platform);

        return $this->success([
            'folder_name' => $folder,
            'platform' => $platform,
            'file_version'  => $version,
        ], "JSON file for folder {$folder}, {$label} version {$version} has been updated successfully.");
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
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $platform = $request->input('platform');
        $version  = $request->input('file_version');
        $folder = $this->normalizeFolderName($request->input('folder_name'));
        $destPath = $this->resolveVersionFilePath($platform, $version, $folder);

        if (!file_exists($destPath)) {
            return $this->error("Version {$version} for folder {$folder}, {$platform} not found", 404);
        }

        unlink($destPath);

        Log::info("Versioned JSON deleted", ['folder_name' => $folder, 'platform' => $platform, 'version' => $version]);

        $label = $this->platformLabel($platform);

        return $this->success(null, "JSON file for folder {$folder}, {$label} version {$version} has been deleted successfully.");
    }

    /**
     * LIST folders used for versioned files
     */
    public function listFolders(Request $request)
    {
        $folders = $this->getFolderNames();
        $data = [];

        foreach ($folders as $folder) {
            $counts = [];
            $total = 0;

            foreach (self::PLATFORMS as $platform) {
                $count = count($this->getVersionsForPlatform($platform, $folder));
                $counts[$platform] = $count;
                $total += $count;
            }

            $folderPath = $this->folderPath($folder);
            $createdAt = is_dir($folderPath) ? date('Y-m-d H:i:s', filemtime($folderPath)) : null;

            $data[] = [
                'folder_name' => $folder,
                'platform_counts' => $counts,
                'total_files' => $total,
                'created_at' => $createdAt,
            ];
        }

        return $this->success($data, 'Folder list loaded successfully.');
    }

    /**
     * LIST files/items inside a selected folder
     */
    public function listFolderItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
            'platform' => ['nullable', 'in:android,ios,debug'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $folder = $this->normalizeFolderName($request->input('folder_name'));
        $platformFilter = $request->input('platform');

        $folderPath = $this->folderPath($folder);
        if (!is_dir($folderPath)) {
            return $this->error("Folder {$folder} not found.", 404);
        }

        $platforms = $platformFilter ? [$platformFilter] : self::PLATFORMS;
        $items = [];

        foreach ($platforms as $platform) {
            $versions = $this->getVersionsForPlatform($platform, $folder);
            foreach ($versions as $version) {
                $filePath = $this->resolveVersionFilePath($platform, $version, $folder);
                $items[] = [
                    'folder_name' => $folder,
                    'platform' => $platform,
                    'file_version' => $version,
                    'relative_path' => "{$folder}/{$platform}/{$version}.json",
                    'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
                    'updated_at' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null,
                ];
            }
        }

        return $this->success([
            'folder_name' => $folder,
            'items' => $items,
            'count' => count($items),
        ], "Loaded " . count($items) . " item(s) from folder {$folder}.");
    }

    /**
     * CREATE folder for versioned files
     */
    public function createFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $folder = $this->normalizeFolderName($request->input('folder_name'));

        $folderPath = $this->folderPath($folder);
        if (is_dir($folderPath)) {
            return $this->error("Folder {$folder} already exists.", 409);
        }

        foreach (self::PLATFORMS as $platform) {
            $platformPath = $this->basePath($platform, $folder);
            if (!is_dir($platformPath)) {
                mkdir($platformPath, 0755, true);
            }
        }

        Log::info('Versioned folder created', ['folder_name' => $folder]);

        return $this->success([
            'folder_name' => $folder,
        ], "Folder {$folder} created successfully.");
    }

    /**
     * RENAME folder for versioned files
     */
    public function renameFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
            'new_folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $oldFolder = $this->normalizeFolderName($request->input('old_folder_name'));
        $newFolder = $this->normalizeFolderName($request->input('new_folder_name'));

        if ($oldFolder === $newFolder) {
            return $this->error('Old and new folder names must be different.', 400);
        }

        $oldPath = $this->folderPath($oldFolder);
        $newPath = $this->folderPath($newFolder);

        if (!is_dir($oldPath)) {
            return $this->error("Folder {$oldFolder} not found.", 404);
        }

        if (is_dir($newPath)) {
            return $this->error("Folder {$newFolder} already exists.", 409);
        }

        if (!@rename($oldPath, $newPath)) {
            return $this->error('Folder rename failed.', 500);
        }

        Log::info('Versioned folder renamed', ['old_folder_name' => $oldFolder, 'new_folder_name' => $newFolder]);

        return $this->success([
            'old_folder_name' => $oldFolder,
            'new_folder_name' => $newFolder,
        ], "Folder renamed from {$oldFolder} to {$newFolder}.");
    }

    /**
     * DELETE folder for versioned files
     */
    public function deleteFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,49}$/'],
            'force' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        $folder = $this->normalizeFolderName($request->input('folder_name'));
        $force = (bool) $request->boolean('force', false);

        $folderPath = $this->folderPath($folder);
        if (!is_dir($folderPath)) {
            return $this->error("Folder {$folder} not found.", 404);
        }

        $hasFiles = false;
        foreach (self::PLATFORMS as $platform) {
            $jsonFiles = glob($this->basePath($platform, $folder) . '/*.json') ?: [];
            if (!empty($jsonFiles)) {
                $hasFiles = true;
                break;
            }
        }

        if ($hasFiles && !$force) {
            return $this->error('Folder is not empty. Set force=true to delete.', 409);
        }

        $this->deleteDirectoryRecursive($folderPath);

        Log::info('Versioned folder deleted', ['folder_name' => $folder, 'force' => $force]);

        return $this->success(null, "Folder {$folder} deleted successfully.");
    }

    private function deleteDirectoryRecursive(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = "{$path}/{$item}";
            if (is_dir($fullPath)) {
                $this->deleteDirectoryRecursive($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
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
