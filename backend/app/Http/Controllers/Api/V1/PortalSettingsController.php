<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PortalSettings;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalSettingsController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $settings = PortalSettings::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['portal_enabled' => false]
        );

        return $this->success($settings, 'Portal settings retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'portal_enabled' => ['sometimes', 'boolean'],
            'custom_logo' => ['nullable', 'string', 'max:500'],
            'custom_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'welcome_message' => ['nullable', 'string'],
            'payment_enabled' => ['sometimes', 'boolean'],
            'quote_acceptance_enabled' => ['sometimes', 'boolean'],
            'stripe_publishable_key' => ['nullable', 'string'],
            'stripe_secret_key' => ['nullable', 'string'],
            'stripe_webhook_secret' => ['nullable', 'string'],
            'payment_methods_enabled' => ['nullable', 'array'],
            'payment_methods_enabled.*' => ['string'],
        ]);

        $settings = PortalSettings::query()->firstOrNew(['user_id' => $user->id]);
        $settings->fill($validated);

        if (! array_key_exists('payment_methods_enabled', $validated) && $settings->payment_methods_enabled === null) {
            $settings->payment_methods_enabled = ['card'];
        }

        $settings->save();

        return $this->success($settings, 'Portal settings updated successfully');
    }
}
