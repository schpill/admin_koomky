<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can convert won lead to client', function () {
    $lead = \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Acme Corp',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
        'phone' => '+1234567890',
    ]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/convert");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'client' => ['id', 'name', 'email', 'phone'],
                'lead' => ['id', 'status', 'converted_at'],
            ],
        ]);

    $this->assertDatabaseHas('clients', [
        'user_id' => $this->user->id,
        'email' => 'john@acme.com',
    ]);
});

test('cannot convert non won lead', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/convert");

    $response->assertStatus(400);
});

test('cannot convert already converted lead', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $lead = \App\Models\Lead::factory()->converted()->create([
        'user_id' => $this->user->id,
        'won_client_id' => $client->id,
    ]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/convert");

    $response->assertStatus(400);
});

test('can override client fields on conversion', function () {
    $lead = \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'email' => 'john@acme.com',
    ]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
        'name' => 'Custom Client Name',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.client.name', 'Custom Client Name');
});
