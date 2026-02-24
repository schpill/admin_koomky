<?php

use App\Models\Client;
use App\Models\User;
use App\Services\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('POST /rag/ask returns answer and sources', function () {
    $user = User::factory()->create();

    $service = Mockery::mock(RagService::class);
    $service->shouldReceive('answer')->andReturn([
        'answer' => 'ok',
        'sources' => [],
        'tokens_used' => 12,
        'latency_ms' => 20,
    ]);
    app()->instance(RagService::class, $service);

    $response = $this->actingAs($user)->postJson('/api/v1/rag/ask', [
        'question' => 'Hello?',
    ]);

    $response->assertStatus(200)->assertJsonPath('data.answer', 'ok');
});

it('POST /rag/ask validates question required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/rag/ask', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['question']);
});

it('POST /rag/ask rejects non owned client_id', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/rag/ask', [
        'question' => 'Hello?',
        'client_id' => $client->id,
    ]);

    $response->assertStatus(403);
});
