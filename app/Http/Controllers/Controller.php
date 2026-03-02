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
        $key = $request->input('key'); // Get key from JSON body

        logger([
            'received_key' => $key,
            'env_key' => env('MODEL_API_KEY'),
        ]);

        if (!$key || $key !== env('MODEL_API_KEY')) {
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
