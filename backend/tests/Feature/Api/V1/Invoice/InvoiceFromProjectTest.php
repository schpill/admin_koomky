<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice can be generated from unbilled project time entries grouped by task', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'in_progress',
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
    ]);

    $taskA = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Design phase',
    ]);

    $taskB = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Development phase',
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $taskA->id,
        'duration_minutes' => 120,
        'is_billed' => false,
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $taskA->id,
        'duration_minutes' => 60,
        'is_billed' => false,
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $taskB->id,
        'duration_minutes' => 180,
        'is_billed' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/generate-invoice');

    $response->assertStatus(201)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.project_id', $project->id)
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseCount('line_items', 2);
    $this->assertDatabaseCount('time_entries', 3);
    $this->assertDatabaseHas('time_entries', ['task_id' => $taskA->id, 'is_billed' => true]);
    $this->assertDatabaseHas('time_entries', ['task_id' => $taskB->id, 'is_billed' => true]);
});
