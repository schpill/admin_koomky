<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Charge;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\StripeObject;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        // Use a global webhook secret from config for simplicity and security.
        // Storing secrets per user and iterating is inefficient and less secure.
        $webhookSecret = config('services.stripe.webhook_secret');
        if (! $webhookSecret) {
            Log::error('Stripe webhook secret is not configured.');

            return $this->error('Webhook configuration error.', 500);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature.', ['exception' => $e->getMessage()]);

            return $this->error('Invalid Stripe signature', 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload.', ['exception' => $e->getMessage()]);

            return $this->error('Invalid payload', 400);
        }

        $stripeObject = $event->data->object;
        $stripeIntentId = $this->resolveStripeIntentId($event->type, $stripeObject);

        if (! $stripeIntentId) {
            return $this->success(null, 'Event for non-local object ignored');
        }

        /** @var PaymentIntent|null $paymentIntent */
        $paymentIntent = PaymentIntent::query()
            ->with(['invoice.user', 'invoice.client'])
            ->where('stripe_payment_intent_id', $stripeIntentId)
            ->first();

        if (! $paymentIntent || ! $paymentIntent->invoice) {
            Log::info('Stripe event for unknown PaymentIntent ignored.', ['stripe_payment_intent_id' => $stripeIntentId]);

            return $this->success(null, 'Event ignored');
        }

        // Idempotency check
        $equivalentStatus = $this->getEquivalentStatus($event->type);
        if ($equivalentStatus && $paymentIntent->status === $equivalentStatus) {
            Log::info('Stripe event already handled.', ['stripe_event' => $event->type, 'payment_intent_id' => $paymentIntent->id]);

            return $this->success(null, 'Event already handled');
        }

        if ($event->type === 'payment_intent.succeeded') {
            if (! ($stripeObject instanceof StripePaymentIntent)) {
                Log::warning('Unexpected Stripe object for payment_intent.succeeded.', ['object_type' => get_class($stripeObject)]);

                return $this->success(null, 'Event ignored');
            }

            return $this->handleSucceeded($paymentIntent, $stripeObject);
        }

        if ($event->type === 'payment_intent.payment_failed') {
            if (! ($stripeObject instanceof StripePaymentIntent)) {
                Log::warning('Unexpected Stripe object for payment_intent.payment_failed.', ['object_type' => get_class($stripeObject)]);

                return $this->success(null, 'Event ignored');
            }

            return $this->handleFailed($paymentIntent, $stripeObject);
        }

        if ($event->type === 'charge.refunded') {
            if (! ($stripeObject instanceof Charge)) {
                Log::warning('Unexpected Stripe object for charge.refunded.', ['object_type' => get_class($stripeObject)]);

                return $this->success(null, 'Event ignored');
            }

            return $this->handleRefunded($paymentIntent, $stripeObject);
        }

        return $this->success(null, 'Event ignored');
    }

    private function handleSucceeded(PaymentIntent $paymentIntent, StripePaymentIntent $stripeObject): JsonResponse
    {
        $invoice = $paymentIntent->invoice;
        if (! $invoice) {
            Log::warning('Stripe succeeded event received without linked invoice.', ['payment_intent_id' => $paymentIntent->id]);

            return $this->success(null, 'Event ignored');
        }

        $amountReceivedInMajorUnit = (float) ($stripeObject->amount_received / 100);

        if (abs($amountReceivedInMajorUnit - $paymentIntent->amount) > 0.01) {
            Log::warning('Stripe payment amount mismatch.', [
                'payment_intent_id' => $paymentIntent->id,
                'expected_amount' => $paymentIntent->amount,
                'received_amount' => $amountReceivedInMajorUnit,
            ]);
        }

        $paymentIntent->update(['status' => 'succeeded', 'failure_reason' => null, 'paid_at' => now()]);

        if (! $invoice->payments()->where('reference', $paymentIntent->stripe_payment_intent_id)->exists()) {
            $invoice->payments()->create([
                'amount' => $amountReceivedInMajorUnit,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'card',
                'reference' => $paymentIntent->stripe_payment_intent_id,
            ]);
        }

        $newBalance = $invoice->total - $invoice->payments()->sum('amount');
        $newStatus = $newBalance <= 0 ? 'paid' : 'partially_paid';

        $invoice->update(['status' => $newStatus, 'paid_at' => $newStatus === 'paid' ? now() : null]);

        if ($invoice->user) {
            $invoice->user->notify(new PaymentReceivedNotification($invoice, $paymentIntent));
        }

        return $this->success(null, 'Stripe event processed');
    }

    private function handleFailed(PaymentIntent $paymentIntent, StripePaymentIntent $stripeObject): JsonResponse
    {
        $invoice = $paymentIntent->invoice;
        if (! $invoice) {
            Log::warning('Stripe failed event received without linked invoice.', ['payment_intent_id' => $paymentIntent->id]);

            return $this->success(null, 'Event ignored');
        }

        $reason = 'Payment failed';
        $lastPaymentError = $stripeObject->last_payment_error;
        if (is_object($lastPaymentError) && isset($lastPaymentError->message)) {
            $reason = (string) $lastPaymentError->message;
        }

        $paymentIntent->update(['status' => 'failed', 'failure_reason' => $reason]);

        $clientEmail = $invoice->client?->email;
        if (is_string($clientEmail) && $clientEmail !== '') {
            Notification::route('mail', $clientEmail)
                ->notify(new PaymentFailedNotification($invoice, $paymentIntent));
        }

        return $this->success(null, 'Stripe event processed');
    }

    private function handleRefunded(PaymentIntent $paymentIntent, Charge $stripeObject): JsonResponse
    {
        $invoice = $paymentIntent->invoice;
        if (! $invoice) {
            Log::warning('Stripe refunded event received without linked invoice.', ['payment_intent_id' => $paymentIntent->id]);

            return $this->success(null, 'Event ignored');
        }

        $paymentIntent->update(['status' => 'refunded', 'refunded_at' => now()]);

        $amountRefundedInMajorUnit = (float) (($stripeObject->amount_refunded ?? 0) / 100);

        if ($amountRefundedInMajorUnit >= (float) $invoice->total) {
            $invoice->update(['status' => 'sent', 'paid_at' => null]);
        }

        return $this->success(null, 'Stripe event processed');
    }

    private function resolveStripeIntentId(string $eventType, StripeObject $object): ?string
    {
        $objectData = $object->toArray();

        if ($eventType === 'charge.refunded') {
            $paymentIntent = $objectData['payment_intent'] ?? null;

            if (is_string($paymentIntent)) {
                return $paymentIntent;
            }

            if (is_array($paymentIntent)) {
                $paymentIntentId = $paymentIntent['id'] ?? null;

                return is_string($paymentIntentId) ? $paymentIntentId : null;
            }

            return null;
        }

        $objectId = $objectData['id'] ?? null;

        return is_string($objectId) ? $objectId : null;
    }

    private function getEquivalentStatus(string $eventType): ?string
    {
        return match ($eventType) {
            'payment_intent.succeeded' => 'succeeded',
            'payment_intent.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
            default => null,
        };
    }
}
