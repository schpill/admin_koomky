<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Product;
use App\Models\ProductCampaignGenerationLog;
use App\Models\Segment;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\ProductCampaignGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductCampaignGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductCampaignGeneratorService $service;

    private $geminiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiService = Mockery::mock(GeminiService::class);
        $this->service = new ProductCampaignGeneratorService($this->geminiService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_generate_creates_campaign_template_in_draft_status(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Test Formation',
            'price' => 1000.00,
            'currency_code' => 'EUR',
        ]);
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('{"subject": "Découvrez notre formation", "html_body": "<p>Contenu HTML</p>"}');

        $campaign = $this->service->generate($product, $segment, $user);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('draft', $campaign->status);
        $this->assertNotNull($campaign->template_id);

        $template = CampaignTemplate::find($campaign->template_id);
        $this->assertInstanceOf(CampaignTemplate::class, $template);
        $this->assertEquals('Découvrez notre formation', $template->subject);
        $this->assertEquals('<p>Contenu HTML</p>', $template->content);
    }

    public function test_generate_parses_json_from_markdown_block(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn("```json\n{\"subject\": \"Sujet test\", \"html_body\": \"<p>HTML</p>\"}\n```");

        $campaign = $this->service->generate($product, $segment, $user);

        $template = CampaignTemplate::find($campaign->template_id);
        $this->assertEquals('Sujet test', $template->subject);
        $this->assertEquals('<p>HTML</p>', $template->content);
    }

    public function test_generate_uses_fallback_when_json_is_invalid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Test Product',
        ]);
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('Invalid JSON response');

        $campaign = $this->service->generate($product, $segment, $user);

        $template = CampaignTemplate::find($campaign->template_id);
        $this->assertNotNull($template->subject);
        $this->assertNotNull($template->content);
        $this->assertStringContainsString('{{product_name}}', $template->content);
    }

    public function test_generate_attaches_product_to_campaign(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('{"subject": "Test", "html_body": "<p>Test</p>"}');

        $campaign = $this->service->generate($product, $segment, $user);

        $this->assertTrue($product->campaigns->contains($campaign));
        $this->assertNotNull($product->campaigns->first()->pivot->generation_model);
        $this->assertNotNull($product->campaigns->first()->pivot->generated_at);
    }

    public function test_generate_creates_success_log_entry(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('{"subject": "Test", "html_body": "<p>Test</p>"}');

        $campaign = $this->service->generate($product, $segment, $user);

        $this->assertDatabaseHas('product_campaign_generation_logs', [
            'product_id' => $product->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'success' => true,
        ]);

        $log = ProductCampaignGenerationLog::where('product_id', $product->id)->first();
        $this->assertNotNull($log->tokens_used);
        $this->assertNotNull($log->latency_ms);
        $this->assertNull($log->error_message);
    }

    public function test_generate_uses_fallback_and_logs_error_when_gemini_throws(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andThrow(new \Exception('Gemini API error'));

        $campaign = $this->service->generate($product, $segment, $user);
        $template = CampaignTemplate::find($campaign->template_id);

        $this->assertNotNull($template);
        $this->assertStringContainsString('{{product_name}}', $template->content);

        $this->assertDatabaseHas('product_campaign_generation_logs', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'success' => false,
        ]);

        $log = ProductCampaignGenerationLog::where('product_id', $product->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Gemini API error', (string) $log->error_message);
    }

    public function test_generate_uses_fallback_when_response_is_empty(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('');

        $campaign = $this->service->generate($product, $segment, $user);
        $template = CampaignTemplate::find($campaign->template_id);

        $this->assertNotNull($template);
        $this->assertStringContainsString('{{product_name}}', $template->content);
    }

    public function test_generate_sets_campaign_name_with_product_and_date(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create(['name' => 'Formation Laravel']);
        $segment = Segment::factory()->for($user)->create();

        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn('{"subject": "Test", "html_body": "<p>Test</p>"}');

        $campaign = $this->service->generate($product, $segment, $user);

        $this->assertStringContainsString('Formation Laravel', $campaign->name);
        $this->assertStringContainsString(now()->format('d/m/Y'), $campaign->name);
    }
}
