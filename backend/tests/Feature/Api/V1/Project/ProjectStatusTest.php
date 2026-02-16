<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('valid project status transition is accepted', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id, [
            'client_id' => $client->id,
            'name' => $project->name,
            'billing_type' => 'hourly',
            'hourly_rate' => 100,
            'status' => 'proposal_sent',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'proposal_sent');
});

test('invalid project status transition returns validation error', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'cancelled',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id, [
            'client_id' => $client->id,
            'name' => $project->name,
            'billing_type' => 'hourly',
            'hourly_rate' => 100,
            'status' => 'in_progress',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid status transition');
});
