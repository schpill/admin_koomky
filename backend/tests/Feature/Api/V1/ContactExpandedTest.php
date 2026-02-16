<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list contacts for their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Contact::factory()->count(3)->create(['client_id' => $client->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/clients/{$client->id}/contacts");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonPath('message', 'Contacts retrieved successfully');

    expect($response->json('data'))->toHaveCount(3);
});

test('user cannot list contacts for another users client', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/clients/{$client->id}/contacts");

    $response->assertStatus(403);
});

test('user can update a contact belonging to their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Old',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}", [
            'first_name' => 'Updated',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'first_name' => 'Updated',
    ]);
});

test('updating contact to primary demotes existing primary', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $primaryContact = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => true,
        'first_name' => 'Primary',
    ]);

    $secondaryContact = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
        'first_name' => 'Secondary',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}/contacts/{$secondaryContact->id}", [
            'first_name' => 'Secondary',
            'is_primary' => true,
        ]);

    $response->assertStatus(200);
    expect($primaryContact->refresh()->is_primary)->toBeFalse();
    expect($secondaryContact->refresh()->is_primary)->toBeTrue();
});

test('user can delete a contact from their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}");

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Contact deleted successfully');

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

test('user cannot delete contact belonging to another users client', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}");

    $response->assertStatus(403);
});

test('deleting contact that does not belong to client returns 404', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $clientB->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$clientA->id}/contacts/{$contact->id}");

    $response->assertStatus(404);
});

test('creating contact without required first_name fails validation', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/contacts", [
            'last_name' => 'Doe',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('first_name');
});

test('creating contact logs an activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/contacts", [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'description' => 'Contact created: Jane Smith',
    ]);
});

test('updating contact logs an activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}", [
            'first_name' => 'Janet',
            'last_name' => 'Smith',
        ]);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'description' => 'Contact updated: Janet Smith',
    ]);
});

test('deleting contact logs an activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}");

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'description' => 'Contact deleted: Jane Smith',
    ]);
});
