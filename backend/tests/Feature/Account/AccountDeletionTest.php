<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('account deletion soft deletes user and user owned records', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account');

    $response->assertOk()
        ->assertJsonPath('status', 'Success');

    $user->refresh();

    expect($user->deleted_at)->not->toBeNull();
    expect($user->deletion_scheduled_at)->not->toBeNull();

    $client = Client::withTrashed()->findOrFail($client->id);
    expect($client->deleted_at)->not->toBeNull();
});
