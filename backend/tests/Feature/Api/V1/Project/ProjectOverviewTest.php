<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project show returns computed overview metrics', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'billing_type' => 'fixed',
        'fixed_price' => 5000,
    ]);

    $doneTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'done',
    ]);

    $todoTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'todo',
    ]);

    TimeEntry::factory()->create([
        'task_id' => $doneTask->id,
        'user_id' => $user->id,
        'duration_minutes' => 120,
    ]);

    TimeEntry::factory()->create([
        'task_id' => $todoTask->id,
        'user_id' => $user->id,
        'duration_minutes' => 60,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/projects/'.$project->id);

    $response->assertStatus(200)
        ->assertJsonPath('data.total_tasks', 2)
        ->assertJsonPath('data.completed_tasks', 1)
        ->assertJsonPath('data.total_time_spent', 180);

    expect((float) $response->json('data.progress_percentage'))->toBe(50.0);
    expect((float) $response->json('data.budget_consumed'))->toBe(2500.0);
});
