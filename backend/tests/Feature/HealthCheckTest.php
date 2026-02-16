<?php

test('health check endpoint returns successful response', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'Success',
            'message' => 'Health check results',
        ]);
});

test('api base route returns 404', function () {
    $response = $this->getJson('/api/v1/non-existent');

    $response->assertStatus(404);
});
