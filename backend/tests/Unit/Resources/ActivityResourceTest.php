<?php

declare(strict_types=1);

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

it('transforms activity into json api structure', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();

    $resource = (new ActivityResource($activity->load('user')))->toArray(new Request);

    expect($resource)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
    expect($resource['type'])->toBe('activity');
    expect($resource['id'])->toBe($activity->id);
});

it('maps type to action in attributes', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create(['type' => 'system']);

    $resource = (new ActivityResource($activity->load('user')))->toArray(new Request);

    expect($resource['attributes']['action'])->toBe('system');
    expect($resource['attributes'])->toHaveKeys(['description', 'changes', 'created_at']);
});

it('includes user in relationships', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();

    $resource = (new ActivityResource($activity->load('user')))->toArray(new Request);

    expect($resource['relationships']['user']['data']['id'])->toBe($user->id);
    expect($resource['relationships']['user']['data']['name'])->toBe($user->name);
});
