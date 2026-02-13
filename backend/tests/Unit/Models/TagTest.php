<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Tag;
use App\Models\User;

it('belongs to a user', function () {
    $tag = Tag::factory()->create();

    expect($tag->user)->toBeInstanceOf(User::class);
});

it('belongs to many clients', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $clients = Client::factory()->count(3)->create(['user_id' => $user->id]);

    $tag->clients()->sync($clients->pluck('id'));

    expect($tag->clients)->toHaveCount(3);
    expect($tag->clients->first())->toBeInstanceOf(Client::class);
});

it('has correct fillable attributes', function () {
    $tag = new Tag;

    expect($tag->getFillable())->toEqual(['user_id', 'name', 'color']);
});

it('uses UUID as primary key', function () {
    $tag = Tag::factory()->create();

    expect($tag->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
