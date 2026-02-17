<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard endpoint responds within performance budget', function () {
    $user = User::factory()->create();

    $startedAt = microtime(true);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard')
        ->assertOk();

    $durationMs = (microtime(true) - $startedAt) * 1000;

    expect($durationMs)->toBeLessThan(500);
});
