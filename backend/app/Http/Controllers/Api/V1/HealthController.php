<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(HealthCheckService $healthCheck): JsonResponse
    {
        $status = $healthCheck->check();

        $statusCode = $status['status'] === 'ok' ? 200 : 503;

        return response()->json($status, $statusCode);
    }
}
