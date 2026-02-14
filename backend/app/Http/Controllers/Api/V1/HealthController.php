<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Connected';
        } catch (\Exception $e) {
            $dbStatus = 'Disconnected: ' . $e->getMessage();
        }

        try {
            Redis::connection()->ping();
            $redisStatus = 'Connected';
        } catch (\Exception $e) {
            $redisStatus = 'Disconnected: ' . $e->getMessage();
        }

        return $this->success([
            'database' => $dbStatus,
            'redis' => $redisStatus,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ], 'Health check results');
    }
}
