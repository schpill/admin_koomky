<?php

use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can get dashboard stats', function () {
    $user = User::factory()->create();
    Client::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'total_clients',
                'active_projects',
                'pending_invoices_amount',
                'recent_activities'
            ]
        ])
        ->assertJsonPath('data.total_clients', 3);
});
