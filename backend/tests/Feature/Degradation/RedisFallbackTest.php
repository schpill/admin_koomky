<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('dashboard works when cache backend is unavailable', function () {
    $user = User::factory()->create();

    Cache::shouldReceive('remember')
        ->andThrow(new RuntimeException('Redis unavailable'));

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonPath('status', 'Success')
        ->assertJsonStructure([
            'data' => ['total_clients', 'active_projects', 'pending_invoices_amount'],
        ]);
});
