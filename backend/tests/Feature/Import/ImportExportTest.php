<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a CSV import template', function () {
    actingAs($this->user)
        ->getJson('/api/v1/import/template')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'content',
                    'filename',
                ],
            ],
        ]);
});

it('returns base64 encoded CSV template', function () {
    $response = actingAs($this->user)
        ->getJson('/api/v1/import/template')
        ->assertStatus(200);

    $content = $response->json('data.attributes.content');
    $decoded = base64_decode($content);

    expect($decoded)->toContain('company_name');
    expect($decoded)->toContain('first_name');
    expect($decoded)->toContain('email');
});

it('exports clients to CSV', function () {
    Client::factory()->count(3)->create(['user_id' => $this->user->id]);

    // Create temp directory if needed
    $tempDir = storage_path('app/temp');
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    actingAs($this->user)
        ->getJson('/api/v1/import/export')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'filename',
                    'size',
                ],
            ],
        ]);
});

it('exports specific clients by IDs', function () {
    $clients = Client::factory()->count(5)->create(['user_id' => $this->user->id]);
    $ids = $clients->take(2)->pluck('id')->toArray();

    $tempDir = storage_path('app/temp');
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    actingAs($this->user)
        ->getJson('/api/v1/import/export?' . http_build_query(['ids' => $ids]))
        ->assertStatus(200);
});

it('requires authentication for template', function () {
    $this->getJson('/api/v1/import/template')
        ->assertStatus(401);
});

it('requires authentication for export', function () {
    $this->getJson('/api/v1/import/export')
        ->assertStatus(401);
});

it('imports clients from CSV file', function () {
    \Illuminate\Support\Facades\Bus::fake();

    $csvContent = "company_name,first_name,last_name,email,phone,vat_number,website,address,city,postal_code,country,notes\n";
    $csvContent .= "Acme Corp,John,Doe,john@acme.com,+33123456789,FR123,https://acme.com,123 Street,Paris,75001,France,Test\n";

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

    actingAs($this->user)
        ->postJson('/api/v1/import/clients', [
            'file' => $file,
        ])
        ->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'total',
                    'batch_id',
                ],
            ],
        ]);
});

it('validates file is required for import', function () {
    actingAs($this->user)
        ->postJson('/api/v1/import/clients', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('validates file type for import', function () {
    $file = \Illuminate\Http\UploadedFile::fake()->create('data.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    actingAs($this->user)
        ->postJson('/api/v1/import/clients', [
            'file' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('requires authentication for import', function () {
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('clients.csv', "header\ndata\n");

    $this->postJson('/api/v1/import/clients', [
        'file' => $file,
    ])
        ->assertStatus(401);
});
