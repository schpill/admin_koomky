<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Services\HealthCheckService;

class HealthController extends Controller
{
    public function __invoke(HealthCheckService $healthCheck): JsonResponse
    {
        $status = $healthCheck->check();

        $statusCode = $status['status'] === 'ok' ? 200 : 503;

        return response()->json($status, $statusCode);
    }
}
