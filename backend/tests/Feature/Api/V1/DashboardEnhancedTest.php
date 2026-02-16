<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard returns enhanced financial metrics trend and upcoming deadlines', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'in_progress',
        'deadline' => now()->addDays(5)->toDateString(),
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => now()->toDateString(),
        'total' => 1000,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(10)->toDateString(),
        'total' => 300,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
        'issue_date' => now()->subMonth()->toDateString(),
        'due_date' => now()->subDays(15)->toDateString(),
        'total' => 400,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_clients', 1)
        ->assertJsonPath('data.pending_invoices_count', 2)
        ->assertJsonPath('data.overdue_invoices_count', 1)
        ->assertJsonPath('data.revenue_year', 1000.0)
        ->assertJsonCount(12, 'data.revenue_trend');

    expect($response->json('data.upcoming_deadlines'))->toHaveCount(1);
});
