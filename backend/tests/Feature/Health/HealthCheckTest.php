<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('enhanced health check exposes per-service statuses', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonPath('status', 'Success')
        ->assertJsonStructure([
            'data' => [
                'overall_status',
                'services' => [
                    'database' => ['status'],
                    'redis' => ['status'],
                    'meilisearch' => ['status'],
                    'queue' => ['status'],
                    'storage' => ['status'],
                ],
            ],
        ]);
});
