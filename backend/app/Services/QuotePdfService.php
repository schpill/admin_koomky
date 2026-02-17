<?php

namespace App\Services;

use App\Models\Quote;

class QuotePdfService extends AbstractPdfService
{
    public function generate(Quote $quote): string
    {
        $quote->loadMissing(['user', 'client', 'lineItems']);

        return $this->renderPdfView('pdf.quote', [
            'quote' => $quote,
            'logoDataUri' => $this->resolveLogoDataUri($quote->user),
        ], 'Quote '.$quote->number);
    }
}
