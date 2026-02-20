<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can list leads', function () {
    \App\Models\Lead::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/v1/leads');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data.data');
});

test('can filter leads by status', function () {
    \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/v1/leads?status=new');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

test('can create lead', function () {
    $response = $this->postJson('/api/v1/leads', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'company_name' => 'Acme Corp',
        'email' => 'john@acme.com',
        'phone' => '+1234567890',
        'source' => 'website',
        'estimated_value' => 10000,
        'currency' => 'EUR',
        'probability' => 50,
        'expected_close_date' => '2024-12-31',
        'notes' => 'Important lead',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.company_name', 'Acme Corp');
});

test('create lead validates required fields', function () {
    $response = $this->postJson('/api/v1/leads', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'last_name']);
});

test('can show lead', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/v1/leads/{$lead->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $lead->id);
});

test('can update lead', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/v1/leads/{$lead->id}", [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.first_name', 'Jane');
});

test('can delete lead', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/v1/leads/{$lead->id}");

    $response->assertStatus(200);
    // With soft delete, the record should still exist but have a deleted_at timestamp
    $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    $this->assertNotNull($lead->fresh()->deleted_at);
});

test('can search leads', function () {
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Acme Corporation',
    ]);
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Other Company',
    ]);

    $response = $this->getJson('/api/v1/leads?search=Acme');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

test('user cannot access other users leads', function () {
    $otherUser = \App\Models\User::factory()->create();
    $lead = \App\Models\Lead::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/v1/leads/{$lead->id}");

    $response->assertStatus(404);
});
