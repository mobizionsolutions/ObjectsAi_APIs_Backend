<?php

namespace App\Http\Controllers\ObjectAi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{

    private function loadCompleteJsonFile(): ?array
    {
         $filePath = base_path('storage/app/object_ai_contents.json');

        // Check if the file exists
        if (!file_exists($filePath)) {
            return null; // Return null if the file does not exist
        }

        // Try to load and decode the JSON file
        $json = file_get_contents($filePath);
        $models = json_decode($json, true);

        // If the decoding fails or the result is not an array, return null
        return is_array($models) ? $models : null;
    }
    private function loadCompleteJsonFileForAndroid(): ?array
    {
         $filePath = base_path('storage/app/object_ai_contents_android.json');

        // Check if the file exists
        if (!file_exists($filePath)) {
            return null; // Return null if the file does not exist
        }

        // Try to load and decode the JSON file
        $json = file_get_contents($filePath);
        $models = json_decode($json, true);

        // If the decoding fails or the result is not an array, return null
        return is_array($models) ? $models : null;
    }
    private function loadCompleteJsonFileForIOS(): ?array
    {
         $filePath = base_path('storage/app/object_ai_contents_ios.json');

        // Check if the file exists
        if (!file_exists($filePath)) {
            return null; // Return null if the file does not exist
        }

        // Try to load and decode the JSON file
        $json = file_get_contents($filePath);
        $models = json_decode($json, true);

        // If the decoding fails or the result is not an array, return null
        return is_array($models) ? $models : null;
    }

    

    public function GetobjectAiJsonFile(Request $request)
    {
        $file = $this->loadCompleteJsonFile();
        if ($file === null) {
            return $this->error('Failed to load content from file', 500);
        }
        return $this->success($file);
    }
    public function GetobjectAiJsonFileForAndroid(Request $request)
    {
        $file = $this->loadCompleteJsonFileForAndroid();
        if ($file === null) {
            return $this->error('Failed to load content from file', 500);
        }
        return $this->success($file);
    }
    public function GetobjectAiJsonFileForIOS(Request $request)
    {
        $file = $this->loadCompleteJsonFileForIOS();
        if ($file === null) {
            return $this->error('Failed to load content from file', 500);
        }
        return $this->success($file);
    }


    public function ObjectAiupdateModelJsonFile(Request $request)
    {
        Log::info("updateAppJsonFile() called", ['input' => $request->all()]);

        $validator = Validator::make($request->all(), [
        'file' => 'required|file|extensions:json'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed", ['errors' => $validator->errors()]);
            return $this->error($validator->errors(), 400);
        }

        $file = $request->file('file');

        Log::info("File received", [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        try {
            $fileContent = file_get_contents($file);
        } catch (\Exception $e) {
            Log::error("Failed to read file", ['exception' => $e->getMessage()]);
            return $this->error("Error reading file", 500);
        }

        $contentBase64 = base64_encode($fileContent);

        Log::info("Uploading file to GitHub...");

        // Upload to GitHub
        try {
            $github = new \App\Providers\GitHubService();
            $response = $github->uploadFile($contentBase64);

            Log::info("GitHub API Response", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                Log::error("GitHub upload failed", [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return $this->error("GitHub upload failed", 500);
            }
        } catch (\Exception $e) {
            Log::error("GitHub Exception", ['exception' => $e->getMessage()]);
            return $this->error("GitHub error occurred", 500);
        }

        // If GitHub upload is successful → save locally
        try {
            $filename = "object_ai_contents.json";
            $file->storeAs('', $filename);

            Log::info("File saved locally", ['filename' => $filename]);
        } catch (\Exception $e) {
            Log::error("Local file save failed", ['exception' => $e->getMessage()]);
            return $this->error("Local file save failed", 500);
        }

        Log::info("File uploaded successfully to GitHub & local storage");

        return $this->success("File uploaded successfully.");
    }
    public function ObjectAiupdateModelJsonFileForAndroid(Request $request)
    {
        Log::info("updateAppJsonFile() called", ['input' => $request->all()]);

        $validator = Validator::make($request->all(), [
        'file' => 'required|file|extensions:json'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed", ['errors' => $validator->errors()]);
            return $this->error($validator->errors(), 400);
        }

        $file = $request->file('file');

        Log::info("File received", [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        try {
            $fileContent = file_get_contents($file);
        } catch (\Exception $e) {
            Log::error("Failed to read file", ['exception' => $e->getMessage()]);
            return $this->error("Error reading file", 500);
        }

        $contentBase64 = base64_encode($fileContent);

        Log::info("Uploading file to GitHub...");

        // Upload to GitHub
        try {
            $github = new \App\Providers\GitHubService();
            $response = $github->uploadFile($contentBase64);

            Log::info("GitHub API Response", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                Log::error("GitHub upload failed", [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return $this->error("GitHub upload failed", 500);
            }
        } catch (\Exception $e) {
            Log::error("GitHub Exception", ['exception' => $e->getMessage()]);
            return $this->error("GitHub error occurred", 500);
        }

        // If GitHub upload is successful → save locally
        try {
            $filename = "object_ai_contents_android.json";
            $file->storeAs('', $filename);

            Log::info("File saved locally", ['filename' => $filename]);
        } catch (\Exception $e) {
            Log::error("Local file save failed", ['exception' => $e->getMessage()]);
            return $this->error("Local file save failed", 500);
        }

        Log::info("File uploaded successfully to GitHub & local storage");

        return $this->success("File uploaded successfully.");
    }
    public function ObjectAiupdateModelJsonFileForIOS(Request $request)
    {
        Log::info("updateAppJsonFile() called", ['input' => $request->all()]);

        $validator = Validator::make($request->all(), [
        'file' => 'required|file|extensions:json'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed", ['errors' => $validator->errors()]);
            return $this->error($validator->errors(), 400);
        }

        $file = $request->file('file');

        Log::info("File received", [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        try {
            $fileContent = file_get_contents($file);
        } catch (\Exception $e) {
            Log::error("Failed to read file", ['exception' => $e->getMessage()]);
            return $this->error("Error reading file", 500);
        }

        $contentBase64 = base64_encode($fileContent);

        Log::info("Uploading file to GitHub...");

        // Upload to GitHub
        try {
            $github = new \App\Providers\GitHubService();
            $response = $github->uploadFile($contentBase64);

            Log::info("GitHub API Response", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                Log::error("GitHub upload failed", [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return $this->error("GitHub upload failed", 500);
            }
        } catch (\Exception $e) {
            Log::error("GitHub Exception", ['exception' => $e->getMessage()]);
            return $this->error("GitHub error occurred", 500);
        }

        // If GitHub upload is successful → save locally
        try {
            $filename = "object_ai_contents_ios.json";
            $file->storeAs('', $filename);

            Log::info("File saved locally", ['filename' => $filename]);
        } catch (\Exception $e) {
            Log::error("Local file save failed", ['exception' => $e->getMessage()]);
            return $this->error("Local file save failed", 500);
        }

        Log::info("File uploaded successfully to GitHub & local storage");

        return $this->success("File uploaded successfully.");
    }

}

