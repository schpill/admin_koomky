<?php

namespace App\Jobs;

use App\Mail\InvoiceSentMail;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $invoiceId)
    {
    }

    public function handle(InvoicePdfService $pdfService): void
    {
        $invoice = Invoice::query()
            ->with(['client', 'user', 'lineItems', 'payments'])
            ->find($this->invoiceId);

        if (! $invoice || ! $invoice->client || ! is_string($invoice->client->email) || $invoice->client->email === '') {
            return;
        }

        $pdfBinary = $pdfService->generate($invoice);
        $path = 'invoices/'.$invoice->id.'.pdf';

        try {
            Storage::disk('local')->makeDirectory('invoices');
            Storage::disk('local')->put($path, $pdfBinary);

            $invoice->update([
                'pdf_path' => $path,
            ]);
        } catch (\Throwable) {
            // In constrained environments (tests/containers), storage may be unavailable.
        }

        Mail::to($invoice->client->email)->send(new InvoiceSentMail($invoice->fresh(), $pdfBinary));
    }
}
