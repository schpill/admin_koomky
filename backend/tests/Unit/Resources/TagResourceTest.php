<?php

declare(strict_types=1);

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

it('transforms tag into json api structure', function () {
    $tag = Tag::factory()->create();

    $resource = (new TagResource($tag))->toArray(new Request);

    expect($resource)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
    expect($resource['type'])->toBe('tag');
    expect($resource['id'])->toBe($tag->id);
});

it('includes name and color attributes', function () {
    $tag = Tag::factory()->create(['name' => 'VIP', 'color' => 'blue']);

    $resource = (new TagResource($tag))->toArray(new Request);
    $attributes = $resource['attributes'];

    expect($attributes['name'])->toBe('VIP');
    expect($attributes['color'])->toBe('blue');
    expect($attributes)->toHaveKeys(['created_at', 'updated_at']);
});

it('includes clients relationship', function () {
    $tag = Tag::factory()->create();

    $resource = (new TagResource($tag))->toArray(new Request);

    expect($resource['relationships'])->toHaveKey('clients');
});
