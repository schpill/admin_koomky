<?php

namespace App\Mail;

use App\Models\CreditNote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreditNoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditNote $creditNote, public string $pdfBinary) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credit note '.$this->creditNote->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-note-sent',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfBinary, $this->creditNote->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
