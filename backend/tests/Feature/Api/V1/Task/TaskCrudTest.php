<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create update and delete task under project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks', [
            'title' => 'Write test plan',
            'description' => 'Add project test matrix',
            'status' => 'todo',
            'priority' => 'high',
            'estimated_hours' => 2,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('data.title', 'Write test plan');

    $taskId = (string) $createResponse->json('data.id');

    $updateResponse = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id.'/tasks/'.$taskId, [
            'title' => 'Write test plan v2',
            'status' => 'in_review',
            'priority' => 'urgent',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.title', 'Write test plan v2')
        ->assertJsonPath('data.priority', 'urgent');

    $deleteResponse = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/projects/'.$project->id.'/tasks/'.$taskId);

    $deleteResponse->assertStatus(200);
    $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
});

test('task index supports filtering by status and priority', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'todo',
        'priority' => 'high',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'done',
        'priority' => 'low',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/projects/'.$project->id.'/tasks?status=todo&priority=high');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'todo');
});

test('user can reorder tasks in bulk', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $taskA = Task::factory()->create(['project_id' => $project->id, 'sort_order' => 0]);
    $taskB = Task::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);
    $taskC = Task::factory()->create(['project_id' => $project->id, 'sort_order' => 2]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks/reorder', [
            'task_ids' => [$taskC->id, $taskA->id, $taskB->id],
        ]);

    $response->assertStatus(200);

    expect(Task::findOrFail($taskC->id)->sort_order)->toBe(0);
    expect(Task::findOrFail($taskA->id)->sort_order)->toBe(1);
    expect(Task::findOrFail($taskB->id)->sort_order)->toBe(2);
});
