<?php

namespace App\Jobs;

use App\Mail\QuoteSentMail;
use App\Models\Quote;
use App\Services\QuotePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendQuoteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $quoteId) {}

    public function handle(QuotePdfService $pdfService): void
    {
        $quote = Quote::query()
            ->with(['client', 'user', 'lineItems'])
            ->find($this->quoteId);

        if (! $quote || ! $quote->client || ! is_string($quote->client->email) || $quote->client->email === '') {
            return;
        }

        $pdfBinary = $pdfService->generate($quote);
        $path = 'quotes/'.$quote->id.'.pdf';

        try {
            Storage::disk('local')->makeDirectory('quotes');
            Storage::disk('local')->put($path, $pdfBinary);

            $quote->update([
                'pdf_path' => $path,
            ]);
        } catch (\Throwable) {
            // In constrained environments (tests/containers), storage may be unavailable.
        }

        $freshQuote = $quote->fresh();

        Mail::to($quote->client->email)->send(new QuoteSentMail($freshQuote ?? $quote, $pdfBinary));
    }
}
