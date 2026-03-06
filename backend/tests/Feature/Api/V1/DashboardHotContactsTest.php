<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard returns hot contacts count for contacts with email score at least 50', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 60,
    ]);
    Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 50,
    ]);
    Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 10,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonPath('data.hot_contacts_count', 2);
});
