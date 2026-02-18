<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public string $magicLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your client portal access link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-invitation',
        );
    }
}
