<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\ActivityService;
use App\Services\WebhookDispatchService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            $client = $invoice->client;
            if ($client) {
                ActivityService::log($client, "Payment received: {$payment->amount} for invoice {$invoice->number}", [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                ]);
            }
        }

        // Dispatch webhook
        $this->dispatchWebhook($payment, 'payment.received');
    }

    /**
     * Dispatch a webhook for the payment event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Payment $payment, string $event, array $extraData = []): void
    {
        $invoice = $payment->invoice;
        if ($invoice === null) {
            return;
        }

        $userId = $invoice->user_id;

        $data = array_merge([
            'id' => $payment->id,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'amount' => (float) $payment->amount,
            'payment_date' => $payment->payment_date->toDateString(),
            'payment_method' => $payment->payment_method,
            'reference' => $payment->reference,
            'client_id' => $invoice->client_id,
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }
}
