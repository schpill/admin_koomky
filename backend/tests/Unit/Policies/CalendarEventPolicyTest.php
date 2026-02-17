<?php

use App\Models\CalendarEvent;
use App\Models\User;
use App\Policies\CalendarEventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('calendar event policy allows viewing any list for authenticated user', function () {
    $user = User::factory()->create();
    $policy = new CalendarEventPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('calendar event policy allows owner and denies non owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = CalendarEvent::factory()->create(['user_id' => $owner->id]);
    $policy = new CalendarEventPolicy;

    expect($policy->view($owner, $event))->toBeTrue();
    expect($policy->view($other, $event))->toBeFalse();
    expect($policy->update($owner, $event))->toBeTrue();
    expect($policy->update($other, $event))->toBeFalse();
    expect($policy->delete($owner, $event))->toBeTrue();
    expect($policy->delete($other, $event))->toBeFalse();
});

test('calendar event policy allows create for persisted user', function () {
    $user = User::factory()->create();
    $policy = new CalendarEventPolicy;

    expect($policy->create($user))->toBeTrue();
});
