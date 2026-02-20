<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookDeliveryController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $endpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $deliveries = $endpoint->deliveries()
            ->latest()
            ->paginate((int) ($request->per_page ?? 15));

        return $this->success($deliveries, 'Webhook deliveries retrieved successfully');
    }

    public function show(Request $request, string $endpointId, string $deliveryId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $endpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $endpointId)
            ->firstOrFail();

        $delivery = $endpoint->deliveries()
            ->where('id', $deliveryId)
            ->firstOrFail();

        return $this->success($delivery, 'Webhook delivery retrieved successfully');
    }

    public function retry(Request $request, string $endpointId, string $deliveryId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $endpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $endpointId)
            ->firstOrFail();

        $delivery = $endpoint->deliveries()
            ->where('id', $deliveryId)
            ->firstOrFail();

        if (! $delivery->canRetry()) {
            return $this->error('Delivery cannot be retried', 400);
        }

        // Dispatch retry
        $service = app(WebhookDispatchService::class);
        $service->retry($delivery);

        return $this->success(null, 'Webhook delivery retry initiated');
    }
}
