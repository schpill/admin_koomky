<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Client;
use App\Models\User;

it('belongs to a user', function () {
    $activity = Activity::factory()->create();

    expect($activity->user)->toBeInstanceOf(User::class);
});

it('belongs to a client', function () {
    $activity = Activity::factory()->create();

    expect($activity->client)->toBeInstanceOf(Client::class);
});

it('casts metadata to array', function () {
    $activity = Activity::factory()->create([
        'metadata' => ['key' => 'value'],
    ]);

    expect($activity->metadata)->toBeArray();
    expect($activity->metadata['key'])->toBe('value');
});

it('has valid activity type constants', function () {
    expect(Activity::TYPE_FINANCIAL)->toBe('financial');
    expect(Activity::TYPE_PROJECT)->toBe('project');
    expect(Activity::TYPE_COMMUNICATION)->toBe('communication');
    expect(Activity::TYPE_NOTE)->toBe('note');
    expect(Activity::TYPE_SYSTEM)->toBe('system');
});

it('uses UUID as primary key', function () {
    $activity = Activity::factory()->create();

    expect($activity->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
