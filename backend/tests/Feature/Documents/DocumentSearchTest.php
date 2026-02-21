<?php

use App\Models\User;
use App\Models\Document;
use App\Enums\DocumentType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it can search by date range', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(10),
    ]);
    Document::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?date_from=' . now()->subDays(5)->toDateString());

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('it filters by multiple tags', function () {
    Document::factory()->create(['user_id' => $this->user->id, 'tags' => ['urgent', 'billing']]);
    Document::factory()->create(['user_id' => $this->user->id, 'tags' => ['personal']]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?tag=urgent');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tags.0', 'urgent');
});
