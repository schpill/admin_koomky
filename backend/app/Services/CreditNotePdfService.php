<?php

namespace App\Services;

use App\Models\CreditNote;

class CreditNotePdfService extends AbstractPdfService
{
    public function generate(CreditNote $creditNote): string
    {
        $creditNote->loadMissing(['user', 'client', 'invoice', 'lineItems']);

        return $this->renderPdfView('pdf.credit-note', [
            'creditNote' => $creditNote,
            'logoDataUri' => $this->resolveLogoDataUri($creditNote->user),
        ], 'Credit note '.$creditNote->number);
    }
}
