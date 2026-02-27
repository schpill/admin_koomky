<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Product;
use App\Models\ProductCampaignGenerationLog;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProductCampaignGeneratorService
{
    public function __construct(
        private readonly GeminiService $geminiService
    ) {}

    /**
     * Generate a campaign from a product using AI.
     */
    public function generate(Product $product, Segment $segment, User $user): Campaign
    {
        $startTime = microtime(true);
        $model = config('services.gemini.generation_model', 'gemini-2.5-flash');

        try {
            // Build the prompt
            $prompt = $this->buildPrompt($product, $user);

            $response = '';
            $fallbackError = null;

            try {
                // Call Gemini
                $response = $this->geminiService->generate($prompt);

                // Parse JSON response
                $parsed = $this->parseJsonResponse($response);
            } catch (\Throwable $geminiException) {
                Log::warning('Gemini generation failed, using fallback content', [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'error' => $geminiException->getMessage(),
                ]);

                $parsed = $this->getFallbackContent();
                $fallbackError = $geminiException->getMessage();
            }

            // Calculate latency
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Create campaign template
            $template = CampaignTemplate::create([
                'user_id' => $user->id,
                'name' => "Template pour {$product->name}",
                'subject' => $parsed['subject'],
                'content' => $parsed['html_body'],
                'type' => 'email',
            ]);

            // Create campaign in draft status
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => "Campagne {$product->name} - ".now()->format('d/m/Y'),
                'segment_id' => $segment->id,
                'template_id' => $template->id,
                'status' => 'draft',
                'type' => 'email',
                'subject' => $parsed['subject'],
                'content' => $parsed['html_body'],
            ]);

            // Attach product to campaign
            $product->campaigns()->attach($campaign->id, [
                'generation_model' => $model,
                'generated_at' => now(),
            ]);

            // Log success
            ProductCampaignGenerationLog::create([
                'product_id' => $product->id,
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'model' => $model,
                'tokens_used' => $this->estimateTokens($response),
                'latency_ms' => $latencyMs,
                'success' => $fallbackError === null,
                'error_message' => $fallbackError,
                'generated_at' => now(),
            ]);

            return $campaign;
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log failure
            ProductCampaignGenerationLog::create([
                'product_id' => $product->id,
                'campaign_id' => null,
                'user_id' => $user->id,
                'model' => $model,
                'tokens_used' => 0,
                'latency_ms' => $latencyMs,
                'success' => false,
                'error_message' => $e->getMessage(),
                'generated_at' => now(),
            ]);

            // Re-throw to let controller handle
            throw $e;
        }
    }

    /**
     * Build the prompt for Gemini.
     */
    private function buildPrompt(Product $product, User $user): string
    {
        $userName = $user->name ?? $user->email;
        $priceFormatted = number_format((float) $product->price, 2).' '.$product->currency_code;
        $type = $product->type->value;

        $duration = '';
        if ($product->duration && $product->duration_unit) {
            $duration = "Durée: {$product->duration} {$product->duration_unit}\n";
        }

        return <<<PROMPT
Tu es un expert en marketing digital. Génère une campagne email professionnelle en français pour promouvoir le produit/service suivant.

INFORMATIONS DU PRODUIT:
Nom: {$product->name}
Type: {$type}
Description: {$product->description}
Prix: {$priceFormatted}
{$duration}Expéditeur: {$userName}

INSTRUCTIONS:
1. Crée un objet d'email accrocheur et professionnel
2. Rédige un corps d'email en HTML (format email marketing)
3. Inclus les variables suivantes: {{first_name}}, {{company}}, {{product_name}}, {{product_price}}, {{unsubscribe_link}}
4. Tone: professionnel mais chaleureux
5. Structure: salutation, valeur du produit, appel à l'action, signature

FORMAT DE RÉPONSE (JSON obligatoire):
{
  "subject": "Objet de l'email",
  "html_body": "<html><body>...contenu HTML...</body></html>"
}

Réponds UNIQUEMENT avec le JSON, sans texte supplémentaire.
PROMPT;
    }

    /**
     * Parse JSON response from Gemini, handling markdown blocks.
     *
     * @return array{subject: string, html_body: string}
     */
    private function parseJsonResponse(string $response): array
    {
        // Extract JSON from markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $response = trim($matches[1]);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::warning('Failed to parse Gemini JSON response, using fallback', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);

            return $this->getFallbackContent();
        }

        if (! isset($decoded['subject']) || ! isset($decoded['html_body'])) {
            Log::warning('Gemini JSON missing required fields, using fallback', [
                'decoded' => $decoded,
            ]);

            return $this->getFallbackContent();
        }

        return [
            'subject' => $decoded['subject'],
            'html_body' => $decoded['html_body'],
        ];
    }

    /**
     * Get fallback content using Blade template.
     *
     * @return array{subject: string, html_body: string}
     */
    private function getFallbackContent(): array
    {
        $html = view('mail.campaign.product-fallback', [
            'first_name' => '{{first_name}}',
            'company' => '{{company}}',
            'product_name' => '{{product_name}}',
            'product_price' => '{{product_price}}',
            'unsubscribe_link' => '{{unsubscribe_link}}',
        ])->render();

        return [
            'subject' => 'Découvrez notre offre : {{product_name}}',
            'html_body' => $html,
        ];
    }

    /**
     * Estimate tokens (rough approximation).
     */
    private function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 characters per token on average
        return (int) ceil(strlen($text) / 4);
    }
}
