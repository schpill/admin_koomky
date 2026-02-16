<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('send endpoint changes draft quote status to sent', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/quotes/'.$quote->id.'/send');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'sent');
});

test('quote status transition validation rejects invalid transition', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/quotes/'.$quote->id, [
            'status' => 'accepted',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'line_items' => [
                [
                    'description' => 'Scope',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'vat_rate' => 20,
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid quote status transition');
});
