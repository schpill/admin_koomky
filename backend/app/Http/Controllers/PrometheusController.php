<?php

namespace App\Http\Controllers;

use App\Services\PrometheusMetricsService;
use Illuminate\Http\Response;

class PrometheusController extends Controller
{
    public function __invoke(PrometheusMetricsService $metricsService): Response
    {
        return response(
            $metricsService->render(),
            200,
            ['Content-Type' => 'text/plain; version=0.0.4; charset=UTF-8']
        );
    }
}
