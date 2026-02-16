<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create update and delete time entry', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/time-entries', [
            'duration_minutes' => 90,
            'date' => now()->toDateString(),
            'description' => 'Implementation work',
        ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('data.duration_minutes', 90);

    $entryId = (string) $createResponse->json('data.id');

    $updateResponse = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/time-entries/'.$entryId, [
            'duration_minutes' => 120,
            'date' => now()->toDateString(),
            'description' => 'Implementation + review',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.duration_minutes', 120);

    $deleteResponse = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/time-entries/'.$entryId);

    $deleteResponse->assertStatus(200);
    $this->assertDatabaseMissing('time_entries', ['id' => $entryId]);
});

test('time entries are aggregated in project computed fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 80,
    ]);
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'duration_minutes' => 150,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/projects/'.$project->id);

    $response->assertStatus(200)
        ->assertJsonPath('data.total_time_spent', 150);

    expect((float) $response->json('data.progress_percentage'))->toBe(100.0);
    expect((float) $response->json('data.budget_consumed'))->toBe(200.0);
});

test('time entry validation rejects non positive duration and future date', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/time-entries', [
            'duration_minutes' => 0,
            'date' => now()->addDay()->toDateString(),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['duration_minutes', 'date']);
});
