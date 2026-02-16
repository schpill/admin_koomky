<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('project factory creates valid model', function () {
    $project = Project::factory()->create();

    expect($project->id)->toBeString();
    expect($project->reference)->toMatch('/^PRJ-\d{4}-\d{4}$/');
    expect($project->user_id)->not()->toBeNull();
    expect($project->client_id)->not()->toBeNull();
});

test('project relationships are configured', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect($project->user)->toBeInstanceOf(User::class);
    expect($project->client)->toBeInstanceOf(Client::class);
    expect($project->tasks->first()?->id)->toBe($task->id);
});

test('project scopes filter by status and client', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'status' => 'in_progress',
    ]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
        'status' => 'draft',
    ]);

    $statusResults = Project::query()->byStatus('in_progress')->get();
    $clientResults = Project::query()->byClient($clientA->id)->get();

    expect($statusResults)->toHaveCount(1);
    expect($clientResults)->toHaveCount(1);
    expect($statusResults->first()->status)->toBe('in_progress');
    expect($clientResults->first()->client_id)->toBe($clientA->id);
});

test('project active scope excludes completed and cancelled statuses', function () {
    Project::factory()->create(['status' => 'in_progress']);
    Project::factory()->create(['status' => 'completed']);
    Project::factory()->create(['status' => 'cancelled']);

    $activeProjects = Project::query()->active()->get();

    expect($activeProjects)->toHaveCount(1);
    expect($activeProjects->first()->status)->toBe('in_progress');
});

test('project validates status transitions', function () {
    $project = Project::factory()->create(['status' => 'cancelled']);

    expect($project->canTransitionTo('in_progress'))->toBeFalse();
    expect($project->canTransitionTo('cancelled'))->toBeTrue();

    $draftProject = Project::factory()->create(['status' => 'draft']);
    expect($draftProject->canTransitionTo('proposal_sent'))->toBeTrue();
});

test('project exposes computed metrics', function () {
    $project = Project::factory()->create([
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
    ]);

    $todoTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'todo',
    ]);

    $doneTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'done',
    ]);

    TimeEntry::factory()->create([
        'task_id' => $todoTask->id,
        'user_id' => $project->user_id,
        'duration_minutes' => 30,
    ]);

    TimeEntry::factory()->create([
        'task_id' => $doneTask->id,
        'user_id' => $project->user_id,
        'duration_minutes' => 90,
    ]);

    $project->refresh();

    expect($project->total_time_spent)->toBe(120);
    expect($project->progress_percentage)->toBe(50.0);
    expect($project->budget_consumed)->toBe(200.0);
});
