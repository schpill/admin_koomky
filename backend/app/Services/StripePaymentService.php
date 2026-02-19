<?php

namespace App\Services;

use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripePaymentService
{
    protected function getStripeClient(PortalSettings $settings): StripeClient
    {
        $this->assertStripeConfigured($settings);
        return new StripeClient($settings->stripe_secret_key);
    }

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
     * @throws ApiErrorException
     */
    public function createPaymentIntent(PaymentIntent $paymentIntent, PortalSettings $settings): array
    {
        $stripe = $this->getStripeClient($settings);

        // Stripe expects the amount in the smallest currency unit (cents)
        $amountInCents = (int) round($paymentIntent->amount * 100);

        $params = [
            'amount' => $amountInCents,
            'currency' => strtolower($paymentIntent->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'internal_payment_intent_id' => $paymentIntent->id,
                'invoice_id' => $paymentIntent->invoice_id,
                'client_id' => $paymentIntent->client_id,
            ],
        ];

        if ($paymentIntent->stripe_payment_intent_id) {
            $stripePaymentIntent = $stripe->paymentIntents->update($paymentIntent->stripe_payment_intent_id, $params);
        } else {
            $stripePaymentIntent = $stripe->paymentIntents->create($params);
        }

        $paymentIntent->forceFill([
            'stripe_payment_intent_id' => $stripePaymentIntent->id,
            'status' => 'processing',
        ])->save();

        return [
            'stripe_payment_intent_id' => $stripePaymentIntent->id,
            'client_secret' => $stripePaymentIntent->client_secret,
            'status' => 'processing',
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws ApiErrorException
     */
    public function refundPayment(PaymentIntent $paymentIntent, float $amount, PortalSettings $settings): array
    {
        $stripe = $this->getStripeClient($settings);

        $amountInCents = (int) round($amount * 100);

        $refund = $stripe->refunds->create([
            'payment_intent' => $paymentIntent->stripe_payment_intent_id,
            'amount' => $amountInCents,
        ]);

        $paymentIntent->forceFill([
            'status' => 'refunded',
            'refunded_at' => now(),
            'metadata' => array_merge($paymentIntent->metadata ?? [], [
                'refund_id' => $refund->id,
                'refunded_amount' => $amount,
            ]),
        ])->save();

        return [
            'status' => 'refunded',
            'refund_id' => $refund->id,
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
