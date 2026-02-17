<?php

namespace App\Services;

use App\Models\Invoice;

class InvoicePdfService extends AbstractPdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['user', 'client', 'lineItems', 'payments']);

        return $this->renderPdfView('pdf.invoice', [
            'invoice' => $invoice,
            'logoDataUri' => $this->resolveLogoDataUri($invoice->user),
        ], 'Invoice '.$invoice->number);
    }

    /**
     * @param  iterable<Invoice>  $invoices
     * @return array<string, string>
     */
    public function generateBatch(iterable $invoices): array
    {
        $documents = [];

        foreach ($invoices as $invoice) {
            $documents[(string) $invoice->id] = $this->generate($invoice);
        }

        return $documents;
    }
}
