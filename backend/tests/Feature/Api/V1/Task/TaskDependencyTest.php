<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can add task dependency', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $task = Task::factory()->create(['project_id' => $project->id]);
    $dependsOn = Task::factory()->create(['project_id' => $project->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/dependencies', [
            'depends_on_task_id' => $dependsOn->id,
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('task_dependencies', [
        'task_id' => $task->id,
        'depends_on_task_id' => $dependsOn->id,
    ]);
});

test('circular task dependency is rejected', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $taskA = Task::factory()->create(['project_id' => $project->id]);
    $taskB = Task::factory()->create(['project_id' => $project->id]);

    $taskA->dependencies()->attach($taskB->id);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/tasks/'.$taskB->id.'/dependencies', [
            'depends_on_task_id' => $taskA->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Circular dependency detected');
});

test('task cannot move to in_progress while dependencies are not done', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'todo',
    ]);

    $dependency = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'todo',
    ]);

    $task->dependencies()->attach($dependency->id);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/projects/'.$project->id.'/tasks/'.$task->id, [
            'title' => $task->title,
            'status' => 'in_progress',
            'priority' => $task->priority,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Task dependencies must be completed before starting this task');
});
