<?php

declare(strict_types=1);

it('returns health status via HTTP endpoint', function () {
    $response = $this->getJson('/api/v1/health');

    expect($response->status())->toBeIn([200, 503]);
    $response->assertJsonStructure([
        'status',
        'timestamp',
        'services',
    ]);
});

it('returns a valid status value', function () {
    $response = $this->getJson('/api/v1/health');

    $status = $response->json('status');
    expect($status)->toBeIn(['ok', 'degraded', 'error']);
});

it('includes postgres service status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertJsonStructure([
        'services' => [
            'postgres' => ['status'],
        ],
    ]);
});

it('includes redis service status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertJsonStructure([
        'services' => [
            'redis' => ['status'],
        ],
    ]);
});

it('does not require authentication', function () {
    $response = $this->getJson('/api/v1/health');
    expect($response->status())->toBeIn([200, 503]);
});
