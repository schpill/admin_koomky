<?php

use App\Models\Campaign;
use App\Models\Product;
use App\Models\Segment;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->segment = Segment::factory()->create(['user_id' => $this->user->id]);
    $this->product = Product::factory()->create(['user_id' => $this->user->id]);
});

afterEach(function () {
    \Mockery::close();
});

test('it generates a campaign from a product', function () {
    // Mock GeminiService
    $this->app->instance(GeminiService::class, \Mockery::mock(GeminiService::class, function ($mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(json_encode([
                'subject' => 'Découvrez notre offre exceptionnelle',
                'html_body' => '<html><body><h1>Offre spéciale</h1></body></html>',
            ]));
    }));

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        ['segment_id' => $this->segment->id]
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.segment_id', $this->segment->id)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'status',
                'segment_id',
                'template_id',
                'redirect_url',
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('campaigns', [
        'status' => 'draft',
        'segment_id' => $this->segment->id,
        'user_id' => $this->user->id,
    ]);

    $this->assertDatabaseHas('campaign_templates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});

test('it prevents using a segment that does not belong to user', function () {
    $otherUser = User::factory()->create();
    $otherSegment = Segment::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        ['segment_id' => $otherSegment->id]
    );

    $response->assertStatus(403);
});

test('it validates segment_id is required', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        []
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['segment_id']);
});

test('it validates segment_id exists', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        ['segment_id' => 'non-existent-uuid']
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['segment_id']);
});

test('it links campaign to product via pivot table', function () {
    // Mock GeminiService
    $this->app->instance(GeminiService::class, \Mockery::mock(GeminiService::class, function ($mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(json_encode([
                'subject' => 'Test',
                'html_body' => '<html><body>Test</body></html>',
            ]));
    }));

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        ['segment_id' => $this->segment->id]
    );

    $response->assertStatus(201);

    $campaignId = $response->json('data.id');
    $this->assertDatabaseHas('product_campaigns', [
        'product_id' => $this->product->id,
        'campaign_id' => $campaignId,
    ]);
});

test('it returns 201 and uses fallback when gemini times out', function () {
    $this->app->instance(GeminiService::class, \Mockery::mock(GeminiService::class, function ($mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new \RuntimeException('Gemini timeout'));
    }));

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/products/{$this->product->id}/campaigns/generate",
        ['segment_id' => $this->segment->id]
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft');

    $campaignId = $response->json('data.id');
    $campaign = Campaign::findOrFail($campaignId);
    $template = $campaign->template;

    expect($template)->not->toBeNull();
    expect($template->content)->toContain('{{product_name}}');
});
