<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    public function uploadFile($contentBase64)
    {
        $owner = env('GITHUB_REPO_OWNER');
        $repo  = env('GITHUB_REPO_NAME');
        $path  = env('GITHUB_FILE_PATH');
        $branch = env('GITHUB_BRANCH', 'main');

        $url = "https://api.github.com/repos/$owner/$repo/contents/$path";

        Log::info("GitHub Upload URL", ['url' => $url]);

        // 1️⃣ Get existing file SHA if exists
        $sha = null;

        $shaResponse = Http::withToken(env('GITHUB_TOKEN'))
            ->get($url, ['ref' => $branch]);

        if ($shaResponse->ok()) {
            $sha = $shaResponse->json()['sha'];
            Log::info("Existing file SHA found", ['sha' => $sha]);
        } else {
            Log::info("No existing file found - will create new file");
        }

        // 2️⃣ Build payload for create or update
        $payload = [
            "message" => "Update ai_art_contents.json via API",
            "content" => $contentBase64,
            "branch"  => $branch,
        ];

        if ($sha) {
            $payload['sha'] = $sha; // Required for updates
        }

        // 3️⃣ Send PUT request to create or update file
        $response = Http::withToken(env('GITHUB_TOKEN'))
            ->put($url, $payload);

        return $response;
    }
}
