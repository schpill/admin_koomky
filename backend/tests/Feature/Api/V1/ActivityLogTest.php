<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a client logs an activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients', [
            'name' => 'Activity Test Corp',
        ]);

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'subject_type' => Client::class,
        'description' => 'Client created: Activity Test Corp',
    ]);
});

test('updating a client logs an activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'New Name',
        ]);

    $this->assertDatabaseHas('activities', [
        'subject_id' => $client->id,
        'description' => 'Client updated: New Name',
    ]);
});
