<?php

namespace App\Jobs;

use App\Mail\CreditNoteSentMail;
use App\Models\CreditNote;
use App\Services\CreditNotePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendCreditNoteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $creditNoteId) {}

    public function handle(CreditNotePdfService $pdfService): void
    {
        $creditNote = CreditNote::query()
            ->with(['client', 'user', 'invoice', 'lineItems'])
            ->find($this->creditNoteId);

        if (! $creditNote || ! $creditNote->client || ! is_string($creditNote->client->email) || $creditNote->client->email === '') {
            return;
        }

        $pdfBinary = $pdfService->generate($creditNote);
        $path = 'credit-notes/'.$creditNote->id.'.pdf';

        try {
            Storage::disk('local')->makeDirectory('credit-notes');
            Storage::disk('local')->put($path, $pdfBinary);

            $creditNote->update([
                'pdf_path' => $path,
            ]);
        } catch (\Throwable) {
            // In constrained environments (tests/containers), storage may be unavailable.
        }

        $freshCreditNote = $creditNote->fresh();

        Mail::to($creditNote->client->email)->send(new CreditNoteSentMail($freshCreditNote ?? $creditNote, $pdfBinary));
    }
}
