<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestTelemetryMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ];

        Log::info('api_request', $context);

        if ($durationMs >= (int) config('performance.slow_request_threshold_ms', 500)) {
            Log::warning('slow_request_detected', $context);
        }

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
