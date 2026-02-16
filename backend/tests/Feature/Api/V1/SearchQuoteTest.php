<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search response includes quotes results', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'name' => 'Acme Labs',
    ]);

    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'number' => 'DEV-2026-0042',
        'notes' => 'SEO package',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/search?q=DEV-2026-0042');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['clients', 'projects', 'tasks', 'invoices', 'quotes'],
        ])
        ->assertJsonPath('data.quotes.0.number', 'DEV-2026-0042');
});
