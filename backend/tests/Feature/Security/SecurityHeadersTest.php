<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('security headers are attached to api responses', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk();

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('X-XSS-Protection'))->toBe('0');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});
