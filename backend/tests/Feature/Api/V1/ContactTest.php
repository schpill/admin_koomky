<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can add a contact to their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/contacts", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.com',
            'position' => 'CEO',
            'is_primary' => true,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('contacts', [
        'client_id' => $client->id,
        'first_name' => 'Jane',
        'is_primary' => true,
    ]);
});

test('only one primary contact per client is enforced', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $contact1 = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => true,
        'first_name' => 'Primary 1',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/contacts", [
            'first_name' => 'Primary 2',
            'last_name' => 'Doe',
            'is_primary' => true,
        ]);

    $response->assertStatus(201);

    // Check that contact1 is no longer primary
    expect($contact1->refresh()->is_primary)->toBeFalse();

    $this->assertDatabaseHas('contacts', [
        'first_name' => 'Primary 2',
        'is_primary' => true,
    ]);
});

test('contact update returns 404 when contact does not belong to the client route parameter', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    $contact = Contact::factory()->create([
        'client_id' => $clientB->id,
        'first_name' => 'Wrong Client',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$clientA->id}/contacts/{$contact->id}", [
            'first_name' => 'Updated Name',
        ]);

    $response->assertStatus(404);
});

test('user can store consent fields when creating a contact', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/contacts", [
            'first_name' => 'Consent',
            'email' => 'consent@example.test',
            'email_consent' => true,
            'email_consent_date' => now()->toDateTimeString(),
            'sms_consent' => true,
            'sms_consent_date' => now()->toDateTimeString(),
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('contacts', [
        'client_id' => $client->id,
        'first_name' => 'Consent',
        'email_consent' => true,
        'sms_consent' => true,
    ]);
});

test('consent date is nulled when consent is explicitly disabled on update', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Consent Update',
        'email_consent' => true,
        'email_consent_date' => now()->subDay(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}/contacts/{$contact->id}", [
            'first_name' => 'Consent Update',
            'email_consent' => false,
            'email_consent_date' => now()->toDateTimeString(),
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'email_consent' => false,
        'email_consent_date' => null,
    ]);
});
