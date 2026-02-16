<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can create a project', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $payload = [
        'client_id' => $client->id,
        'name' => 'Website Redesign',
        'description' => 'UI and UX revamp',
        'billing_type' => 'hourly',
        'hourly_rate' => 120,
        'estimated_hours' => 50,
        'start_date' => now()->toDateString(),
        'deadline' => now()->addMonth()->toDateString(),
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Website Redesign');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'client_id' => $client->id,
        'name' => 'Website Redesign',
    ]);

    $project = Project::first();
    expect($project->reference)->toMatch('/^PRJ-\d{4}-\d{4}$/');
});

test('project index supports filtering sorting and pagination', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Project::factory()->count(2)->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'in_progress',
    ]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    Project::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/projects?status=in_progress&client_id='.$client->id.'&sort_by=created_at&sort_order=asc&per_page=1');

    $response->assertStatus(200)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonCount(1, 'data.data');
});

test('user can update and delete own project', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $updateResponse = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id, [
            'client_id' => $client->id,
            'name' => 'Updated Project',
            'billing_type' => 'fixed',
            'fixed_price' => 2500,
            'status' => 'proposal_sent',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Project')
        ->assertJsonPath('data.status', 'proposal_sent');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project',
        'status' => 'proposal_sent',
    ]);

    $deleteResponse = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/projects/'.$project->id);

    $deleteResponse->assertStatus(200);
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('user cannot access another users project', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($intruder, 'sanctum')
        ->getJson('/api/v1/projects/'.$project->id);

    $response->assertForbidden();
});
