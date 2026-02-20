<?php

use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Policies\WebhookEndpointPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('any user can view all webhook endpoints (viewAny)', function () {
    $user = User::factory()->create();
    $policy = new WebhookEndpointPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('user can view their own webhook endpoint', function () {
    $user = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $user->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->view($user, $endpoint))->toBeTrue();
});

test('user cannot view another users webhook endpoint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $otherUser->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->view($user, $endpoint))->toBeFalse();
});

test('any user can create webhook endpoints', function () {
    $user = User::factory()->create();
    $policy = new WebhookEndpointPolicy;

    expect($policy->create($user))->toBeTrue();
});

test('user can update their own webhook endpoint', function () {
    $user = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $user->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->update($user, $endpoint))->toBeTrue();
});

test('user cannot update another users webhook endpoint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $otherUser->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->update($user, $endpoint))->toBeFalse();
});

test('user can delete their own webhook endpoint', function () {
    $user = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $user->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->delete($user, $endpoint))->toBeTrue();
});

test('user cannot delete another users webhook endpoint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $endpoint = WebhookEndpoint::factory()->create(['user_id' => $otherUser->id]);
    $policy = new WebhookEndpointPolicy;

    expect($policy->delete($user, $endpoint))->toBeFalse();
});
