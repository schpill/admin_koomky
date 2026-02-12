<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckService
{
    public function check(): array
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        // Check PostgreSQL
        try {
            DB::connection()->getPdo();
            $checks['services']['postgres'] = [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'error';
            $checks['services']['postgres'] = [
                'status' => 'error',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }

        // Check Redis
        try {
            Redis::connection()->ping();
            $checks['services']['redis'] = [
                'status' => 'ok',
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'error';
            $checks['services']['redis'] = [
                'status' => 'error',
                'message' => 'Redis connection failed: '.$e->getMessage(),
            ];
        }

        // Check Meilisearch
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get(config('scout.meilisearch.host').'/health', [
                'timeout' => 2,
            ]);

            if ($response->getStatusCode() === 200) {
                $checks['services']['meilisearch'] = [
                    'status' => 'ok',
                    'message' => 'Meilisearch is healthy',
                ];
            } else {
                throw new \Exception('Meilisearch health check failed');
            }
        } catch (\Exception $e) {
            $checks['status'] = 'degraded';
            $checks['services']['meilisearch'] = [
                'status' => 'error',
                'message' => 'Meilisearch unavailable: '.$e->getMessage(),
            ];
        }

        return $checks;
    }
}
