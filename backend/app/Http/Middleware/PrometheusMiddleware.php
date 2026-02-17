<?php

namespace App\Http\Middleware;

use App\Services\PrometheusMetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function __construct(private readonly PrometheusMetricsService $metricsService) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationSeconds = microtime(true) - $startedAt;
        $requestSize = (float) ((int) ($request->headers->get('content-length') ?? 0));
        $route = $request->route();
        $routePath = $route !== null
            ? '/'.ltrim((string) $route->uri(), '/')
            : '/'.ltrim($request->path(), '/');

        $counterLabels = [
            'method' => strtoupper($request->method()),
            'path' => $routePath,
            'status' => (string) $response->getStatusCode(),
        ];
        $histogramLabels = [
            'method' => strtoupper($request->method()),
            'path' => $routePath,
        ];

        $this->metricsService->incrementCounter('http_requests_total', $counterLabels);
        $this->metricsService->observeHistogram('http_request_duration_seconds', $durationSeconds, $histogramLabels);
        $this->metricsService->observeHistogram('http_request_size_bytes', $requestSize, $histogramLabels);

        return $response;
    }
}
