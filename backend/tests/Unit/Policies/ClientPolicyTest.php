<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use App\Policies\ClientPolicy;

beforeEach(function () {
    $this->policy = new ClientPolicy;
});

it('allows owner to view their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    expect($this->policy->view($user, $client))->toBeTrue();
});

it('denies non-owner from viewing client', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $owner->id]);

    expect($this->policy->view($other, $client))->toBeFalse();
});

it('allows owner to update their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    expect($this->policy->update($user, $client))->toBeTrue();
});

it('denies non-owner from updating client', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $owner->id]);

    expect($this->policy->update($other, $client))->toBeFalse();
});

it('allows owner to delete their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    expect($this->policy->delete($user, $client))->toBeTrue();
});

it('denies non-owner from deleting client', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $owner->id]);

    expect($this->policy->delete($other, $client))->toBeFalse();
});
