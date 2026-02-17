<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prometheus middleware records http request counter and duration histogram', function () {
    $this->getJson('/api/v1/health')->assertStatus(200);

    $response = $this->get('/metrics');
    $response->assertStatus(200);

    $body = (string) $response->getContent();

    expect($body)->toContain('http_requests_total');
    expect($body)->toContain('http_request_duration_seconds');
    expect($body)->toContain('method="GET"');
    expect($body)->toContain('status="200"');
});
