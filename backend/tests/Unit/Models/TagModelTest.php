<?php

use App\Models\Client;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('tag belongs to a user', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    expect($tag->user)->toBeInstanceOf(User::class);
    expect($tag->user->id)->toBe($user->id);
});

test('tag has many clients through pivot', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    $tag->clients()->attach([$clientA->id, $clientB->id]);

    expect($tag->clients)->toHaveCount(2);
    expect($tag->clients->pluck('id')->toArray())->toContain($clientA->id);
    expect($tag->clients->pluck('id')->toArray())->toContain($clientB->id);
});

test('tag uses uuid as primary key', function () {
    $tag = Tag::factory()->create();

    expect($tag->id)->toBeString();
    expect(strlen($tag->id))->toBe(36);
});

test('tag fillable attributes are set correctly', function () {
    $fillable = (new Tag())->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('name');
    expect($fillable)->toContain('color');
});

test('tag uses HasFactory trait', function () {
    $tag = Tag::factory()->create();

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->exists)->toBeTrue();
});

test('user has many tags', function () {
    $user = User::factory()->create();
    Tag::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->tags)->toHaveCount(3);
});
