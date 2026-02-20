<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookEndpointController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WebhookDispatchService $webhookDispatchService
    ) {}

    /**
     * List webhook endpoints.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $endpoints = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($endpoint): array => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
                'url' => $endpoint->url,
                'events' => $endpoint->events,
                'events_count' => count($endpoint->events ?? []),
                'is_active' => $endpoint->is_active,
                'last_triggered_at' => $endpoint->last_triggered_at?->toIso8601String(),
                'created_at' => $endpoint->created_at->toIso8601String(),
            ]);

        return $this->success([
            'data' => $endpoints,
        ], 'Webhook endpoints retrieved successfully');
    }

    /**
     * Create a webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:500', 'starts_with:https'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in($this->getAvailableEvents())],
        ]);

        $user = $request->user();
        $secret = Str::random(32);

        $endpoint = WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return $this->success([
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'secret' => $secret, // Only shown once on creation
            'is_active' => $endpoint->is_active,
            'created_at' => $endpoint->created_at->toIso8601String(),
        ], 'Webhook endpoint created successfully', 201);
    }

    /**
     * Show a webhook endpoint.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return $this->success([
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'is_active' => $endpoint->is_active,
            'last_triggered_at' => $endpoint->last_triggered_at?->toIso8601String(),
            'created_at' => $endpoint->created_at->toIso8601String(),
            'updated_at' => $endpoint->updated_at->toIso8601String(),
        ], 'Webhook endpoint retrieved successfully');
    }

    /**
     * Update a webhook endpoint.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'url' => ['sometimes', 'url', 'max:500', 'starts_with:https'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', Rule::in($this->getAvailableEvents())],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $endpoint->fill($validated);
        $endpoint->save();

        return $this->success([
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'is_active' => $endpoint->is_active,
            'updated_at' => $endpoint->updated_at->toIso8601String(),
        ], 'Webhook endpoint updated successfully');
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $endpoint->delete();

        return $this->success(null, 'Webhook endpoint deleted successfully');
    }

    /**
     * Send test payload to endpoint.
     */
    public function test(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $result = $this->webhookDispatchService->sendTestPayload($endpoint);

        return $this->success($result, $result['success'] ? 'Test webhook sent successfully' : 'Test webhook failed');
    }

    /**
     * Get available webhook events.
     *
     * @return array<int, string>
     */
    private function getAvailableEvents(): array
    {
        return [
            'invoice.created',
            'invoice.sent',
            'invoice.paid',
            'invoice.overdue',
            'invoice.cancelled',
            'quote.sent',
            'quote.accepted',
            'quote.rejected',
            'quote.expired',
            'expense.created',
            'expense.updated',
            'expense.deleted',
            'project.completed',
            'project.cancelled',
            'payment.received',
            'lead.created',
            'lead.status_changed',
            'lead.converted',
        ];
    }
}
