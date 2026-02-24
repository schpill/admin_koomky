<?php

use App\Jobs\ProcessDocumentEmbeddingJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('GET /rag/status returns documents with embedding_status', function () {
    $user = User::factory()->create();
    Document::factory()->create(['user_id' => $user->id, 'embedding_status' => 'indexed']);

    $response = $this->actingAs($user)->getJson('/api/v1/rag/status');

    $response->assertStatus(200)->assertJsonPath('data.data.0.embedding_status', 'indexed');
});

it('POST /rag/reindex/{id} dispatches embedding job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $document = Document::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/rag/reindex/'.$document->id);

    $response->assertStatus(202);
    Queue::assertPushed(ProcessDocumentEmbeddingJob::class);
});
