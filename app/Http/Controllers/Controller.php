<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function __construct(Request $request)
    {
        $key = $request->input('organization_key'); // Get key from JSON body

        // load organizations from environment variable
        // expected format: { "organizations": [ {"organization_name": "...", "organization_key": "..."}, ... ] }
        $orgJson = env('ORGANIZATIONS');
        $valid = [];
        if ($orgJson) {
            $data = json_decode($orgJson, true);
            if (is_array($data) && isset($data['organizations']) && is_array($data['organizations'])) {
                foreach ($data['organizations'] as $org) {
                    if (isset($org['organization_key'])) {
                        $valid[] = $org['organization_key'];
                    }
                }
            }
        }

        logger([
            'received_key' => $key,
            'valid_keys'   => $valid,
        ]);

        if (!$key || !in_array($key, $valid, true)) {
            throw new HttpResponseException(response()->json([
                'status' => false,
                'message' => 'Unauthorized – invalid or missing API key',
            ], 401));
        }
    }

    protected function success($data = [], string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function error(string $message = 'Something went wrong', int $status = 500): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], $status);
    }
}
