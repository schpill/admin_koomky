<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Meilisearch\Client as MeilisearchClient;
use Throwable;

class HealthController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->databaseStatus(),
            'redis' => $this->redisStatus(),
            'meilisearch' => $this->meilisearchStatus(),
            'queue' => $this->queueStatus(),
            'storage' => $this->storageStatus(),
            'cache' => $this->cacheStatus(),
        ];

        $overallStatus = collect($services)
            ->every(fn (array $service): bool => $service['status'] === 'up')
            ? 'healthy'
            : 'degraded';

        return $this->success([
            'overall_status' => $overallStatus,
            'services' => $services,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ], 'Health check results');
    }

    /**
     * @return array{status:string, message:string}
     */
    private function databaseStatus(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'up',
                'message' => 'Database connection available',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'down',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{status:string, message:string}
     */
    private function redisStatus(): array
    {
        try {
            Redis::connection()->ping();

            return [
                'status' => 'up',
                'message' => 'Redis connection available',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'down',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{status:string, message:string}
     */
    private function meilisearchStatus(): array
    {
        if (config('scout.driver') !== 'meilisearch') {
            return [
                'status' => 'up',
                'message' => 'Meilisearch disabled in current environment',
            ];
        }

        try {
            $meilisearch = new MeilisearchClient(
                (string) config('scout.meilisearch.host'),
                (string) config('scout.meilisearch.key')
            );
            $meilisearch->health();

            return [
                'status' => 'up',
                'message' => 'Meilisearch connection available',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'down',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{status:string, message:string}
     */
    private function queueStatus(): array
    {
        $driver = (string) config('queue.default');

        if ($driver === 'sync') {
            return [
                'status' => 'up',
                'message' => 'Queue driver is sync',
            ];
        }

        if ($driver === 'redis') {
            return $this->redisStatus();
        }

        return [
            'status' => 'up',
            'message' => sprintf('Queue driver [%s] configured', $driver),
        ];
    }

    /**
     * @return array{status:string, message:string}
     */
    private function storageStatus(): array
    {
        $disk = (string) config('filesystems.default', 'local');
        $path = sprintf('health/%s.txt', str_replace('.', '', uniqid('', true)));

        try {
            $storage = Storage::disk($disk);
            $storage->put($path, 'ok');
            $storage->delete($path);

            return [
                'status' => 'up',
                'message' => sprintf('Storage disk [%s] writable', $disk),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'down',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{status:string, message:string}
     */
    private function cacheStatus(): array
    {
        try {
            Cache::put('healthcheck:cache', 'ok', 10);
            Cache::forget('healthcheck:cache');

            return [
                'status' => 'up',
                'message' => 'Cache write/read available',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'down',
                'message' => $exception->getMessage(),
            ];
        }
    }
}
