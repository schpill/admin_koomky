<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
});

it('creates a contact for a client', function () {
    actingAs($this->user)
        ->postJson("/api/v1/clients/{$this->client->id}/contacts", [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '+33 6 12 34 56 78',
            'position' => 'CTO',
            'is_primary' => true,
        ])
        ->assertStatus(201);

    expect($this->client->contacts()->count())->toBe(1);
});

it('validates name is required when creating contact', function () {
    actingAs($this->user)
        ->postJson("/api/v1/clients/{$this->client->id}/contacts", [
            'email' => 'test@example.com',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('shows a contact', function () {
    $contact = Contact::factory()->create(['client_id' => $this->client->id]);

    actingAs($this->user)
        ->getJson("/api/v1/clients/{$this->client->id}/contacts/{$contact->id}")
        ->assertStatus(200);
});

it('updates a contact', function () {
    $contact = Contact::factory()->create(['client_id' => $this->client->id]);

    actingAs($this->user)
        ->putJson("/api/v1/clients/{$this->client->id}/contacts/{$contact->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertStatus(200);

    expect($contact->fresh()->email)->toBe('updated@example.com');
});

it('deletes a contact', function () {
    $contact = Contact::factory()->create(['client_id' => $this->client->id]);

    actingAs($this->user)
        ->deleteJson("/api/v1/clients/{$this->client->id}/contacts/{$contact->id}")
        ->assertStatus(204);

    expect(Contact::find($contact->id))->toBeNull();
});

it('sets other contacts as non-primary when creating a primary contact', function () {
    $existing = Contact::factory()->primary()->create(['client_id' => $this->client->id]);

    actingAs($this->user)
        ->postJson("/api/v1/clients/{$this->client->id}/contacts", [
            'name' => 'New Primary',
            'email' => 'new@example.com',
            'is_primary' => true,
        ])
        ->assertStatus(201);

    expect($existing->fresh()->is_primary)->toBeFalse();
});

it('logs activity when creating a contact', function () {
    actingAs($this->user)
        ->postJson("/api/v1/clients/{$this->client->id}/contacts", [
            'name' => 'Contact Name',
        ])
        ->assertStatus(201);

    expect($this->client->activities()->count())->toBeGreaterThanOrEqual(1);
});

// TODO: ContactController lacks authorization - any authenticated user can create contacts on any client
// This test documents the current (insecure) behavior. Should return 403 after adding $this->authorize('update', $client).
it('allows creating contacts on another users client (missing authorization)', function () {
    $otherUser = User::factory()->create();
    $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

    actingAs($this->user)
        ->postJson("/api/v1/clients/{$otherClient->id}/contacts", [
            'name' => 'Intruder Contact',
        ])
        ->assertStatus(201);
});
