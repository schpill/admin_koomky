<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('api requests expose request id and emit slow request warning', function () {
    config()->set('performance.slow_request_threshold_ms', 0);

    Log::spy();

    $response = $this->getJson('/api/v1/health');

    $response->assertOk();
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();

    Log::shouldHaveReceived('info')->with('api_request', Mockery::on(function (array $context): bool {
        return isset($context['request_id'], $context['method'], $context['path'], $context['duration_ms']);
    }))->once();

    Log::shouldHaveReceived('warning')->with('slow_request_detected', Mockery::on(function (array $context): bool {
        return isset($context['request_id'], $context['duration_ms']) && $context['duration_ms'] >= 0;
    }))->once();
});
