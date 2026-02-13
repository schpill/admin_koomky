<?php

declare(strict_types=1);

use App\Http\Resources\TagCollection;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

it('wraps tags in data key', function () {
    $user = User::factory()->create();
    $tags = Tag::factory()->for($user)->count(2)->create();

    $collection = (new TagCollection($tags))->toArray(new Request);

    expect($collection)->toHaveKey('data');
    expect($collection['data'])->toHaveCount(2);
});
