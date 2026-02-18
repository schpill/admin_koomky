<?php

namespace App\Services;

use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class StripePaymentService
{
    /**
     * @return array<string, string|null>
     */
    public function configurationForUser(User $user): array
    {
        $settings = PortalSettings::query()->firstWhere('user_id', $user->id);

        return [
            'publishable_key' => $settings?->stripe_publishable_key,
            'secret_key' => $settings?->stripe_secret_key,
            'webhook_secret' => $settings?->stripe_webhook_secret,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createPaymentIntent(PaymentIntent $paymentIntent, PortalSettings $settings): array
    {
        $this->assertStripeConfigured($settings);

        $stripePaymentIntentId = $paymentIntent->stripe_payment_intent_id ?: 'pi_'.strtolower(Str::random(24));
        $clientSecret = hash('sha256', $stripePaymentIntentId.'|'.$paymentIntent->id.'|'.$settings->stripe_secret_key);

        $metadata = $paymentIntent->metadata ?? [];
        $metadata['client_secret'] = $clientSecret;

        $paymentIntent->forceFill([
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'status' => 'processing',
            'metadata' => $metadata,
        ])->save();

        return [
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'client_secret' => $clientSecret,
            'status' => 'processing',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmPayment(PaymentIntent $paymentIntent, PortalSettings $settings): array
    {
        $this->assertStripeConfigured($settings);

        return [
            'stripe_payment_intent_id' => $paymentIntent->stripe_payment_intent_id,
            'status' => $paymentIntent->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refundPayment(PaymentIntent $paymentIntent, float $amount, PortalSettings $settings): array
    {
        $this->assertStripeConfigured($settings);

        $metadata = $paymentIntent->metadata ?? [];
        $metadata['refunded_amount'] = round($amount, 2);
        $metadata['refund_id'] = 're_'.strtolower(Str::random(20));

        $paymentIntent->forceFill([
            'status' => 'refunded',
            'refunded_at' => now(),
            'metadata' => $metadata,
        ])->save();

        return [
            'status' => 'refunded',
            'refund_id' => $metadata['refund_id'],
        ];
    }

    private function assertStripeConfigured(PortalSettings $settings): void
    {
        if (
            ! is_string($settings->stripe_publishable_key) || $settings->stripe_publishable_key === ''
            || ! is_string($settings->stripe_secret_key) || $settings->stripe_secret_key === ''
        ) {
            throw new RuntimeException('Stripe is not configured for this account');
        }
    }
}
