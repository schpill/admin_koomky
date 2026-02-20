<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookEndpointController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $endpoints = WebhookEndpoint::where('user_id', $user->id)
            ->withCount('deliveries')
            ->latest()
            ->paginate((int) ($request->per_page ?? 15));

        return $this->success($endpoints, 'Webhook endpoints retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500|starts_with:https://',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);

        /** @var User $user */
        $user = $request->user();

        $secret = Str::random(32);

        $endpoint = WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'url' => $request->url,
            'secret' => $secret,
            'events' => $request->events,
            'is_active' => true,
        ]);

        return $this->success([
            ...$endpoint->toArray(),
            'secret' => $secret, // Show secret only once
        ], 'Webhook endpoint created successfully', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $webhookEndpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return $this->success($webhookEndpoint, 'Webhook endpoint retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $webhookEndpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:500|starts_with:https://',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string',
            'is_active' => 'sometimes|boolean',
        ]);

        $webhookEndpoint->update($request->only(['name', 'url', 'events', 'is_active']));

        return $this->success($webhookEndpoint, 'Webhook endpoint updated successfully');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $webhookEndpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $webhookEndpoint->delete();

        return $this->success(null, 'Webhook endpoint deleted successfully');
    }

    public function test(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $webhookEndpoint = WebhookEndpoint::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // Dispatch test webhook (in production, this would go to a queue)
        app(\App\Services\WebhookDispatchService::class)->dispatchTest($webhookEndpoint);

        return $this->success(null, 'Test webhook sent successfully');
    }
}
