<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();
    User::unsetEventDispatcher();
});

it('creates a new client', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'company' => 'Acme Corp',
            'phone' => '+33 1 23 45 67 89',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.attributes.name', 'Acme Corporation')
        ->assertJsonPath('data.attributes.email', 'contact@acme.com');

    expect(Client::where('name', 'Acme Corporation')->exists())->toBeTrue();
});

it('requires a name', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'email' => 'test@example.com',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('creates contacts when provided', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corporation',
            'contacts' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@acme.com',
                    'position' => 'CEO',
                    'is_primary' => true,
                ],
            ],
        ])
        ->assertStatus(201)
        ->assertJsonCount(1, 'data.relationships.contacts.data');
});

it('creates tags when provided', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corporation',
            'tags' => ['VIP', 'Enterprise'],
        ])
        ->assertStatus(201);

    $client = Client::where('name', 'Acme Corporation')->first();
    expect($client->tags)->toHaveCount(2);
});

it('generates a unique reference', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corporation',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.attributes.reference')
        ->assertJsonPath('data.attributes.reference', '/^CLI-\d{8}-\d{4}$/');
});

it('logs activity on creation', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corporation',
        ])
        ->assertStatus(201);

    $client = Client::where('name', 'Acme Corporation')->first();
    expect($client->activities()->count())->toBe(1);
    expect($client->activities()->first()->action)->toBe('created');
});
