<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ProductSale;
use App\Models\ReminderSequence;
use App\Services\ActivityService;
use App\Services\Calendar\CalendarAutoEventService;
use App\Services\ReminderDispatchService;
use App\Services\WebhookDispatchService;
use Illuminate\Support\Facades\DB;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        app(CalendarAutoEventService::class)->syncInvoiceReminder($invoice);

        $client = $invoice->client;
        if ($client) {
            ActivityService::log($client, "Invoice created: {$invoice->number}", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'status' => $invoice->status,
            ]);
        }

        $this->syncCounterFromNumber($invoice->number);

        // Dispatch webhook
        $this->dispatchWebhook($invoice, 'invoice.created');
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged(['due_date', 'number'])) {
            app(CalendarAutoEventService::class)->syncInvoiceReminder($invoice);
        }

        if (! $invoice->wasChanged('status')) {
            return;
        }

        $client = $invoice->client;
        if (! $client) {
            return;
        }

        $previousStatus = (string) $invoice->getOriginal('status');
        $newStatus = (string) $invoice->status;

        ActivityService::log($client, "Invoice status changed: {$invoice->number}", [
            'invoice_id' => $invoice->id,
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);

        if (in_array($newStatus, ['sent', 'paid', 'overdue'], true)) {
            ActivityService::log($client, "Invoice {$newStatus}: {$invoice->number}", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'status' => $newStatus,
            ]);
        }

        // Dispatch status-specific webhook
        $webhookEvent = match ($newStatus) {
            'sent' => 'invoice.sent',
            'paid' => 'invoice.paid',
            'overdue' => 'invoice.overdue',
            'cancelled' => 'invoice.cancelled',
            default => null,
        };

        if ($webhookEvent !== null) {
            $this->dispatchWebhook($invoice, $webhookEvent, [
                'previous_status' => $previousStatus,
            ]);
        }

        if ($newStatus === 'overdue') {
            $this->startReminderSchedule($invoice);
        }

        if (in_array($newStatus, ['paid', 'cancelled'], true)) {
            app(ReminderDispatchService::class)->completeSchedule($invoice);
        }

        // Create ProductSale records when invoice is paid
        if ($newStatus === 'paid') {
            $this->createProductSales($invoice);
        }
    }

    /**
     * Create ProductSale records for line items with products.
     */
    private function createProductSales(Invoice $invoice): void
    {
        $invoice->loadMissing('lineItems.product');

        foreach ($invoice->lineItems as $lineItem) {
            if (! $lineItem->product_id) {
                continue;
            }

            // Use firstOrCreate to prevent duplicates
            ProductSale::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'product_id' => $lineItem->product_id,
                ],
                [
                    'user_id' => $invoice->user_id,
                    'client_id' => $invoice->client_id,
                    'quantity' => $lineItem->quantity,
                    'unit_price' => $lineItem->unit_price,
                    'total_price' => $lineItem->total,
                    'currency_code' => $invoice->currency,
                    'status' => 'confirmed',
                    'sold_at' => now(),
                ]
            );

            // Dispatch product.sold webhook
            $this->dispatchProductSoldWebhook($invoice, $lineItem);
        }
    }

    /**
     * Dispatch product.sold webhook.
     */
    private function dispatchProductSoldWebhook(Invoice $invoice, mixed $lineItem): void
    {
        $product = $lineItem->product;
        if (! $product) {
            return;
        }

        $sale = ProductSale::where('invoice_id', $invoice->id)
            ->where('product_id', $lineItem->product_id)
            ->first();

        if (! $sale) {
            return;
        }

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'client_id' => $invoice->client_id,
            'sale_id' => $sale->id,
            'total_price' => (float) $sale->total_price,
            'currency_code' => $sale->currency_code,
            'sold_at' => $sale->sold_at?->toIso8601String(),
        ];

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch('product.sold', $data, $invoice->user_id);
    }

    private function syncCounterFromNumber(string $number): void
    {
        if (! preg_match('/^([A-Z]+)-(\d{4})-(\d{4})$/', $number, $matches)) {
            return;
        }

        $prefix = $matches[1];
        $year = $matches[2];
        $value = (int) $matches[3];
        $counterKey = "invoices:{$prefix}:{$year}";

        DB::transaction(function () use ($counterKey, $value): void {
            $counter = DB::table('reference_counters')
                ->where('counter_key', $counterKey)
                ->lockForUpdate()
                ->first();

            $now = now();

            if (! $counter) {
                DB::table('reference_counters')->insert([
                    'counter_key' => $counterKey,
                    'last_number' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }

            if ((int) $counter->last_number >= $value) {
                return;
            }

            DB::table('reference_counters')
                ->where('counter_key', $counterKey)
                ->update([
                    'last_number' => $value,
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Dispatch a webhook for the invoice event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Invoice $invoice, string $event, array $extraData = []): void
    {
        $userId = $invoice->user_id;

        $data = array_merge([
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status,
            'total' => (float) $invoice->total,
            'currency' => $invoice->currency,
            'client_id' => $invoice->client_id,
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }

    private function startReminderSchedule(Invoice $invoice): void
    {
        $existingSchedule = InvoiceReminderSchedule::query()
            ->where('invoice_id', $invoice->id)
            ->exists();

        if ($existingSchedule) {
            return;
        }

        $defaultSequence = ReminderSequence::query()
            ->where('user_id', $invoice->user_id)
            ->active()
            ->default()
            ->with('steps')
            ->first();

        if (! $defaultSequence) {
            return;
        }

        $firstStep = $defaultSequence->steps()->orderBy('step_number')->first();

        InvoiceReminderSchedule::query()->create([
            'invoice_id' => $invoice->id,
            'sequence_id' => $defaultSequence->id,
            'user_id' => $invoice->user_id,
            'started_at' => $invoice->due_date,
            'completed_at' => null,
            'is_paused' => false,
            'next_reminder_step_id' => $firstStep?->id,
        ]);
    }
}
