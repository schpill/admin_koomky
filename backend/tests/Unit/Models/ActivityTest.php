<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('activity belongs to a user', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Test activity',
    ]);

    expect($activity->user)->toBeInstanceOf(User::class);
    expect($activity->user->id)->toBe($user->id);
});

test('activity has a morph-to subject relationship', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Test activity',
    ]);

    expect($activity->subject)->toBeInstanceOf(Client::class);
    expect($activity->subject->id)->toBe($client->id);
});

test('activity casts metadata to array', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Test',
        'metadata' => ['action' => 'create', 'fields' => ['name']],
    ]);

    $activity->refresh();
    expect($activity->metadata)->toBeArray();
    expect($activity->metadata['action'])->toBe('create');
});

test('activity uses uuid as primary key', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'UUID test',
    ]);

    expect($activity->id)->toBeString();
    expect(strlen($activity->id))->toBe(36);
});

test('activity fillable attributes are set correctly', function () {
    $fillable = (new Activity())->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('subject_id');
    expect($fillable)->toContain('subject_type');
    expect($fillable)->toContain('description');
    expect($fillable)->toContain('metadata');
});
