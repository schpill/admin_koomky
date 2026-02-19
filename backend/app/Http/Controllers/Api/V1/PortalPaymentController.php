<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Services\PortalActivityLogger;
use App\Services\StripePaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class PortalPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StripePaymentService $stripePaymentService,
        private readonly PortalActivityLogger $portalActivityLogger,
    ) {}

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessInvoice($client, $invoice)) {
            return $this->error('Invoice not found', 404);
        }

        if ($invoice->status === 'paid') {
            return $this->error('Invoice is already paid', 422);
        }

        $settings = $request->attributes->get('portal_settings');
        if (! $settings instanceof PortalSettings || ! $settings->payment_enabled) {
            return $this->error('Portal payments are disabled', 403);
        }

        $paymentIntent = PaymentIntent::query()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => (float) $invoice->balance_due,
            'currency' => $invoice->currency,
            'status' => 'pending',
        ]);

        try {
            $stripePayload = $this->stripePaymentService->createPaymentIntent($paymentIntent, $settings);
        } catch (ApiErrorException $exception) {
            // Log the error for debugging
            logs()->error('Stripe API error on payment intent creation', [
                'error' => $exception->getMessage(),
                'invoice_id' => $invoice->id,
            ]);
            return $this->error('Could not initiate payment. Please try again later.', 500);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $paymentIntent->refresh();

        $this->portalActivityLogger->log(
            $request,
            $client,
            'make_payment',
            $this->resolvePortalAccessToken($request),
            Invoice::class,
            $invoice->id
        );

        return $this->success([
            'id' => $paymentIntent->id,
            'invoice_id' => $paymentIntent->invoice_id,
            'client_id' => $paymentIntent->client_id,
            'stripe_payment_intent_id' => $paymentIntent->stripe_payment_intent_id,
            'amount' => (float) $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'status' => $paymentIntent->status,
            'client_secret' => $stripePayload['client_secret'] ?? null,
        ], 'Payment intent created successfully', 201);
    }

    public function paymentStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessInvoice($client, $invoice)) {
            return $this->error('Invoice not found', 404);
        }

        /** @var PaymentIntent|null $paymentIntent */
        $paymentIntent = PaymentIntent::query()
            ->where('invoice_id', $invoice->id)
            ->where('client_id', $client->id)
            ->latest()
            ->first();

        if (! $paymentIntent) {
            return $this->error('No payment intent found for invoice', 404);
        }

        return $this->success([
            'id' => $paymentIntent->id,
            'invoice_id' => $paymentIntent->invoice_id,
            'status' => $paymentIntent->status,
            'stripe_payment_intent_id' => $paymentIntent->stripe_payment_intent_id,
            'failure_reason' => $paymentIntent->failure_reason,
        ], 'Payment status retrieved successfully');
    }

    private function resolveClient(Request $request): ?Client
    {
        $client = $request->attributes->get('portal_client');

        return $client instanceof Client ? $client : null;
    }

    private function resolvePortalAccessToken(Request $request): ?PortalAccessToken
    {
        $portalAccessToken = $request->attributes->get('portal_access_token');

        return $portalAccessToken instanceof PortalAccessToken ? $portalAccessToken : null;
    }

    private function canAccessInvoice(Client $client, Invoice $invoice): bool
    {
        return $invoice->client_id === $client->id
            && in_array($invoice->status, ['sent', 'viewed', 'partially_paid', 'overdue', 'paid'], true);
    }
}
