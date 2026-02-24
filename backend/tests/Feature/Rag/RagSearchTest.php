<?php

use App\Models\User;
use App\Services\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('GET /rag/search returns chunks', function () {
    $user = User::factory()->create();

    $service = Mockery::mock(VectorSearchService::class);
    $service->shouldReceive('search')->andReturn(collect([
        ['document_id' => '1', 'content' => 'chunk', 'score' => 0.8],
    ]));

    app()->instance(VectorSearchService::class, $service);

    $response = $this->actingAs($user)->getJson('/api/v1/rag/search?q=bonjour&limit=3');

    $response->assertStatus(200)->assertJsonPath('data.0.content', 'chunk');
});
