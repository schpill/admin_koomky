<?php

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('task factory creates valid model', function () {
    $task = Task::factory()->create();

    expect($task->id)->toBeString();
    expect($task->project_id)->not()->toBeNull();
    expect($task->status)->toBeString();
    expect($task->priority)->toBeString();
});

test('task relationships are configured', function () {
    $task = Task::factory()->create();
    $dependency = Task::factory()->create(['project_id' => $task->project_id, 'status' => 'todo']);

    $task->dependencies()->attach($dependency->id);
    $task->refresh();

    expect($task->project)->not()->toBeNull();
    expect($task->dependencies->first()?->id)->toBe($dependency->id);
});

test('task scopes filter by status and priority', function () {
    Task::factory()->create(['status' => 'in_progress', 'priority' => 'high']);
    Task::factory()->create(['status' => 'todo', 'priority' => 'low']);

    $statusResults = Task::query()->byStatus('in_progress')->get();
    $priorityResults = Task::query()->byPriority('high')->get();

    expect($statusResults)->toHaveCount(1);
    expect($priorityResults)->toHaveCount(1);
});

test('task overdue scope returns tasks due before today and not done', function () {
    Task::factory()->create([
        'status' => 'todo',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    Task::factory()->create([
        'status' => 'done',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $overdue = Task::query()->overdue()->get();

    expect($overdue)->toHaveCount(1);
    expect($overdue->first()->status)->toBe('todo');
});

test('task cannot move to in_progress when dependency is not done', function () {
    $task = Task::factory()->create(['status' => 'todo']);
    $dependency = Task::factory()->create(['project_id' => $task->project_id, 'status' => 'todo']);

    $task->dependencies()->attach($dependency->id);
    $task->refresh();

    expect($task->canTransitionTo('in_progress'))->toBeFalse();

    $dependency->update(['status' => 'done']);
    $task->refresh();

    expect($task->canTransitionTo('in_progress'))->toBeTrue();
});
