<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('csp header is included on api responses', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk();

    expect((string) $response->headers->get('Content-Security-Policy'))
        ->toMatch("/default-src 'self'/");
});
