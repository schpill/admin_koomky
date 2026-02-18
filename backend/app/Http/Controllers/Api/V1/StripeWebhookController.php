<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class StripeWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $eventType = (string) ($payload['type'] ?? '');
        $object = is_array($payload['data']['object'] ?? null) ? $payload['data']['object'] : [];
        $stripeIntentId = $this->resolveStripeIntentId($eventType, $object);

        /** @var PaymentIntent|null $paymentIntent */
        $paymentIntent = PaymentIntent::query()
            ->with(['invoice.user', 'invoice.client'])
            ->where('stripe_payment_intent_id', $stripeIntentId)
            ->first();

        if (! $this->isValidSignature($request->getContent(), (string) $request->header('Stripe-Signature', ''), $paymentIntent)) {
            return $this->error('Invalid Stripe signature', 400);
        }

        if (! $paymentIntent || ! $paymentIntent->invoice) {
            return $this->success(null, 'Event ignored');
        }

        return match ($eventType) {
            'payment_intent.succeeded' => $this->handleSucceeded($paymentIntent),
            'payment_intent.payment_failed' => $this->handleFailed($paymentIntent, $object),
            'charge.refunded' => $this->handleRefunded($paymentIntent, $object),
            default => $this->success(null, 'Event ignored'),
        };
    }

    private function handleSucceeded(PaymentIntent $paymentIntent): JsonResponse
    {
        /** @var Invoice $invoice */
        $invoice = $paymentIntent->invoice;

        $paymentIntent->forceFill([
            'status' => 'succeeded',
            'failure_reason' => null,
            'paid_at' => now(),
        ])->save();

        $alreadyRecorded = $invoice->payments()
            ->where('reference', $paymentIntent->stripe_payment_intent_id)
            ->exists();

        if (! $alreadyRecorded) {
            $invoice->payments()->create([
                'amount' => (float) $paymentIntent->amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'card',
                'reference' => $paymentIntent->stripe_payment_intent_id,
            ]);
        }

        $invoice->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
        ])->save();

        if ($invoice->user) {
            $invoice->user->notify(new PaymentReceivedNotification($invoice, $paymentIntent));
        }

        return $this->success(null, 'Stripe event processed');
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handleFailed(PaymentIntent $paymentIntent, array $object): JsonResponse
    {
        /** @var Invoice $invoice */
        $invoice = $paymentIntent->invoice;

        $reason = is_array($object['last_payment_error'] ?? null)
            ? (string) ($object['last_payment_error']['message'] ?? 'Payment failed')
            : 'Payment failed';

        $paymentIntent->forceFill([
            'status' => 'failed',
            'failure_reason' => $reason,
        ])->save();

        if ($invoice->client && is_string($invoice->client->email) && $invoice->client->email !== '') {
            Notification::route('mail', $invoice->client->email)
                ->notify(new PaymentFailedNotification($invoice, $paymentIntent));
        }

        return $this->success(null, 'Stripe event processed');
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handleRefunded(PaymentIntent $paymentIntent, array $object): JsonResponse
    {
        /** @var Invoice $invoice */
        $invoice = $paymentIntent->invoice;

        $paymentIntent->forceFill([
            'status' => 'refunded',
            'refunded_at' => now(),
        ])->save();

        $amountRefunded = (float) (($object['amount_refunded'] ?? 0) / 100);
        if ($amountRefunded >= (float) $invoice->total) {
            $invoice->forceFill([
                'status' => 'sent',
                'paid_at' => null,
            ])->save();
        }

        return $this->success(null, 'Stripe event processed');
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function resolveStripeIntentId(string $eventType, array $object): string
    {
        return match ($eventType) {
            'charge.refunded' => (string) ($object['payment_intent'] ?? ''),
            default => (string) ($object['id'] ?? ''),
        };
    }

    private function isValidSignature(string $payload, string $providedSignature, ?PaymentIntent $paymentIntent): bool
    {
        if ($providedSignature === '') {
            return false;
        }

        if ($paymentIntent && $paymentIntent->invoice && $paymentIntent->invoice->user) {
            $settings = PortalSettings::query()->firstWhere('user_id', $paymentIntent->invoice->user->id);
            if ($settings && is_string($settings->stripe_webhook_secret) && $settings->stripe_webhook_secret !== '') {
                $expected = hash_hmac('sha256', $payload, $settings->stripe_webhook_secret);

                return hash_equals($expected, $providedSignature);
            }
        }

        $settingsWithSecret = PortalSettings::query()
            ->whereNotNull('stripe_webhook_secret')
            ->get();

        foreach ($settingsWithSecret as $settings) {
            if (! is_string($settings->stripe_webhook_secret) || $settings->stripe_webhook_secret === '') {
                continue;
            }

            $expected = hash_hmac('sha256', $payload, $settings->stripe_webhook_secret);
            if (hash_equals($expected, $providedSignature)) {
                return true;
            }
        }

        return false;
    }
}
