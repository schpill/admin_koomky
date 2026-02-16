<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('outstanding report groups invoices by aging and excludes paid', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'due_date' => now()->subDays(20)->toDateString(),
        'total' => 100,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
        'due_date' => now()->subDays(45)->toDateString(),
        'total' => 200,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'due_date' => now()->subDays(90)->toDateString(),
        'total' => 300,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/outstanding');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_outstanding', 300.0)
        ->assertJsonPath('data.total_invoices', 2)
        ->assertJsonCount(2, 'data.items');

    expect($response->json('data.aging.0_30.count'))->toBe(1);
    expect($response->json('data.aging.31_60.count'))->toBe(1);
});
