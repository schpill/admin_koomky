<?php

use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('any user can view all leads (viewAny)', function () {
    $user = User::factory()->create();
    $policy = new LeadPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('user can view their own lead', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $user->id]);
    $policy = new LeadPolicy;

    expect($policy->view($user, $lead))->toBeTrue();
});

test('user cannot view another users lead', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $otherUser->id]);
    $policy = new LeadPolicy;

    expect($policy->view($user, $lead))->toBeFalse();
});

test('any user can create leads', function () {
    $user = User::factory()->create();
    $policy = new LeadPolicy;

    expect($policy->create($user))->toBeTrue();
});

test('user can update their own lead', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $user->id]);
    $policy = new LeadPolicy;

    expect($policy->update($user, $lead))->toBeTrue();
});

test('user cannot update another users lead', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $otherUser->id]);
    $policy = new LeadPolicy;

    expect($policy->update($user, $lead))->toBeFalse();
});

test('user can delete their own lead', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $user->id]);
    $policy = new LeadPolicy;

    expect($policy->delete($user, $lead))->toBeTrue();
});

test('user cannot delete another users lead', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $otherUser->id]);
    $policy = new LeadPolicy;

    expect($policy->delete($user, $lead))->toBeFalse();
});
