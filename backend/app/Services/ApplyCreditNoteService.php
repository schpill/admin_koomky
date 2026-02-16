<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use RuntimeException;

class ApplyCreditNoteService
{
    public function apply(CreditNote $creditNote): CreditNote
    {
        $creditNote->loadMissing('invoice');

        if ($creditNote->status === 'applied') {
            throw new RuntimeException('Credit note has already been applied');
        }

        $invoice = $creditNote->invoice;
        if (! ($invoice instanceof Invoice)) {
            throw new RuntimeException('Credit note invoice not found');
        }

        if ((float) $creditNote->total > (float) $invoice->balance_due) {
            throw new RuntimeException('Credit note total exceeds invoice remaining balance');
        }

        $invoice->payments()->create([
            'amount' => (float) $creditNote->total,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'credit_note',
            'reference' => $creditNote->number,
            'notes' => 'Applied from credit note '.$creditNote->number,
        ]);

        $invoice->refresh();

        if ((float) $invoice->amount_paid >= (float) $invoice->total) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ((float) $invoice->amount_paid > 0 && $invoice->status !== 'partially_paid') {
            $invoice->update([
                'status' => 'partially_paid',
            ]);
        }

        $creditNote->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        return $creditNote->fresh(['client', 'invoice', 'lineItems']) ?? $creditNote;
    }
}
