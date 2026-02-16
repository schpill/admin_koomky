<?php

use App\Models\Tag;
use App\Models\User;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('any user can view all tags (viewAny)', function () {
    $user = User::factory()->create();
    $policy = new TagPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('user can view their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $policy = new TagPolicy;

    expect($policy->view($user, $tag))->toBeTrue();
});

test('user cannot view another users tag', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
    $policy = new TagPolicy;

    expect($policy->view($user, $tag))->toBeFalse();
});

test('any user can create tags', function () {
    $user = User::factory()->create();
    $policy = new TagPolicy;

    expect($policy->create($user))->toBeTrue();
});

test('user can update their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $policy = new TagPolicy;

    expect($policy->update($user, $tag))->toBeTrue();
});

test('user cannot update another users tag', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
    $policy = new TagPolicy;

    expect($policy->update($user, $tag))->toBeFalse();
});

test('user can delete their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $policy = new TagPolicy;

    expect($policy->delete($user, $tag))->toBeTrue();
});

test('user cannot delete another users tag', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
    $policy = new TagPolicy;

    expect($policy->delete($user, $tag))->toBeFalse();
});
