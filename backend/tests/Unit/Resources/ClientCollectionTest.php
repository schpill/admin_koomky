<?php

declare(strict_types=1);

use App\Http\Resources\ClientCollection;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

it('wraps clients in data key', function () {
    $user = User::factory()->create();
    User::unsetEventDispatcher();
    $clients = Client::factory()->for($user)->count(3)->create();

    $collection = (new ClientCollection($clients))->toArray(new Request);

    expect($collection)->toHaveKey('data');
    expect($collection['data'])->toHaveCount(3);
});

it('returns empty data for empty collection', function () {
    $collection = (new ClientCollection(collect()))->toArray(new Request);

    expect($collection['data'])->toHaveCount(0);
});
