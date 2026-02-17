<?php

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('project deadline creates calendar event automatically', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'name' => 'Website redesign',
        'deadline' => '2026-04-15',
    ]);

    $event = CalendarEvent::query()
        ->where('user_id', $user->id)
        ->where('eventable_type', Project::class)
        ->where('eventable_id', $project->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event?->type)->toBe('deadline');
    expect($event?->title)->toContain('Website redesign');
});

test('task due date creates calendar event automatically', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Prepare project brief',
        'due_date' => '2026-04-20',
    ]);

    $event = CalendarEvent::query()
        ->where('user_id', $user->id)
        ->where('eventable_type', Task::class)
        ->where('eventable_id', $task->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event?->type)->toBe('task');
    expect($event?->title)->toContain('Prepare project brief');
});

test('invoice due date creates reminder event three days before', function () {
    Carbon::setTestNow('2026-03-01 09:00:00');

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'number' => 'FAC-2026-1450',
        'due_date' => '2026-03-10',
    ]);

    $event = CalendarEvent::query()
        ->where('user_id', $user->id)
        ->where('eventable_type', Invoice::class)
        ->where('eventable_id', $invoice->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event?->type)->toBe('reminder');
    expect($event?->start_at->toDateString())->toBe('2026-03-07');

    Carbon::setTestNow();
});
