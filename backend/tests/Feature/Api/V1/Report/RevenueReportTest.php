<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('revenue report aggregates totals by month and supports filters', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'project_id' => $project->id,
        'status' => 'paid',
        'issue_date' => '2026-01-10',
        'total' => 1200,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
        'status' => 'partially_paid',
        'issue_date' => '2026-02-08',
        'total' => 800,
    ]);

    Invoice::factory()->create([
        'user_id' => $other->id,
        'status' => 'paid',
        'issue_date' => '2026-01-10',
        'total' => 9999,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/revenue?date_from=2026-01-01&date_to=2026-12-31');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_revenue', 2000.0)
        ->assertJsonPath('data.count', 2)
        ->assertJsonCount(2, 'data.by_month');

    $filtered = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/revenue?date_from=2026-01-01&date_to=2026-12-31&client_id='.$clientA->id.'&project_id='.$project->id);

    $filtered->assertStatus(200)
        ->assertJsonPath('data.total_revenue', 1200.0)
        ->assertJsonPath('data.count', 1);
});
