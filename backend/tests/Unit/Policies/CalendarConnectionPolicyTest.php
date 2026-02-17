<?php

use App\Models\CalendarConnection;
use App\Models\User;
use App\Policies\CalendarConnectionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('calendar connection policy allows viewing any list for authenticated user', function () {
    $user = User::factory()->create();
    $policy = new CalendarConnectionPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('calendar connection policy allows owner and denies non owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = CalendarConnection::factory()->create(['user_id' => $owner->id]);
    $policy = new CalendarConnectionPolicy;

    expect($policy->view($owner, $connection))->toBeTrue();
    expect($policy->view($other, $connection))->toBeFalse();
    expect($policy->update($owner, $connection))->toBeTrue();
    expect($policy->update($other, $connection))->toBeFalse();
    expect($policy->delete($owner, $connection))->toBeTrue();
    expect($policy->delete($other, $connection))->toBeFalse();
});

test('calendar connection policy allows create for persisted user', function () {
    $user = User::factory()->create();
    $policy = new CalendarConnectionPolicy;

    expect($policy->create($user))->toBeTrue();
});
