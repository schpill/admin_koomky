<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('user can export clients to csv', function () {
    $user = User::factory()->create();
    Client::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/clients/export/csv');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('user can import clients from csv using exported header format', function () {
    $user = User::factory()->create();

    $content = 'Reference,Name,Email,Phone,City,Country,Status
';
    $content .= 'CLI-2026-0001,CSV Client,csv@example.com,+33600000000,Paris,France,inactive';

    $file = UploadedFile::fake()->createWithContent('clients.csv', $content);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients/import/csv', [
            'file' => $file,
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('clients', [
        'name' => 'CSV Client',
        'email' => 'csv@example.com',
        'city' => 'Paris',
        'country' => 'France',
        'status' => 'inactive',
        'user_id' => $user->id,
    ]);
});
