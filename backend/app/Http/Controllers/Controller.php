<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use DispatchesJobs;

    /**
     * Success response helper.
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'message' => $message,
            ],
        ], $statusCode);
    }

    /**
     * Error response helper.
     */
    protected function errorResponse(string $message, int $statusCode, array $errors = []): JsonResponse
    {
        $response = [
            'error' => [
                'message' => $message,
                'status' => $statusCode,
            ],
        ];

        if (! empty($errors)) {
            $response['error']['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
