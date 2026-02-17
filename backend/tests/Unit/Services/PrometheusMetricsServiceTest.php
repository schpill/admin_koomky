<?php

use App\Services\PrometheusMetricsService;
use Tests\TestCase;

uses(TestCase::class);

test('prometheus metrics service increments counter sets gauge and observes histogram', function () {
    $service = app(PrometheusMetricsService::class);
    $service->reset();

    $service->incrementCounter('test_counter_total', ['channel' => 'api'], 2);
    $service->setGauge('test_gauge', 42.5, ['unit' => 'ms']);
    $service->observeHistogram('test_histogram_seconds', 0.250, ['method' => 'GET']);

    $output = $service->render();

    expect($output)->toContain('test_counter_total');
    expect($output)->toContain('channel="api"');
    expect($output)->toContain('test_gauge');
    expect($output)->toContain('test_histogram_seconds');
});
