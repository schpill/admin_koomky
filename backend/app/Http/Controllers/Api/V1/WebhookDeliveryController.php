<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookDeliveryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WebhookDispatchService $webhookDispatchService
    ) {}

    /**
     * List deliveries for an endpoint.
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $deliveries = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpoint->id)
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->input('per_page', 15));

        $data = collect($deliveries->items())->map(fn ($delivery): array => [
            'id' => $delivery->id,
            'event' => $delivery->event,
            'attempt_count' => $delivery->attempt_count,
            'response_status' => $delivery->response_status,
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'failed_at' => $delivery->failed_at?->toIso8601String(),
            'created_at' => $delivery->created_at->toIso8601String(),
            'status' => $delivery->delivered_at ? 'delivered' : ($delivery->failed_at ? 'failed' : 'pending'),
        ]);

        return $this->success([
            'data' => $data,
            'current_page' => $deliveries->currentPage(),
            'total' => $deliveries->total(),
            'last_page' => $deliveries->lastPage(),
        ], 'Webhook deliveries retrieved successfully');
    }

    /**
     * Show a specific delivery.
     */
    public function show(Request $request, string $endpointId, string $deliveryId): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $endpointId)
            ->firstOrFail();

        $delivery = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpoint->id)
            ->where('id', $deliveryId)
            ->firstOrFail();

        return $this->success([
            'id' => $delivery->id,
            'event' => $delivery->event,
            'payload' => $delivery->payload,
            'response_status' => $delivery->response_status,
            'response_body' => $delivery->response_body,
            'attempt_count' => $delivery->attempt_count,
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'failed_at' => $delivery->failed_at?->toIso8601String(),
            'next_retry_at' => $delivery->next_retry_at?->toIso8601String(),
            'created_at' => $delivery->created_at->toIso8601String(),
        ], 'Webhook delivery retrieved successfully');
    }

    /**
     * Manually retry a failed delivery.
     */
    public function retry(Request $request, string $endpointId, string $deliveryId): JsonResponse
    {
        $user = $request->user();

        $endpoint = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('id', $endpointId)
            ->firstOrFail();

        $delivery = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpoint->id)
            ->where('id', $deliveryId)
            ->firstOrFail();

        if (! $delivery->canRetry()) {
            return $this->error('Maximum retry attempts exceeded', 400);
        }

        $delivery = $this->webhookDispatchService->retryDelivery($delivery);

        return $this->success([
            'id' => $delivery->id,
            'attempt_count' => $delivery->attempt_count,
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'failed_at' => $delivery->failed_at?->toIso8601String(),
            'status' => $delivery->delivered_at ? 'delivered' : ($delivery->failed_at ? 'failed' : 'pending'),
        ], $delivery->delivered_at ? 'Webhook delivery successful' : 'Webhook delivery failed');
    }
}
