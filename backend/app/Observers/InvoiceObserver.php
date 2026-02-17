<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\ActivityService;
use App\Services\Calendar\CalendarAutoEventService;
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
}
