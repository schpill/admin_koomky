<?php

namespace App\Mail\Tickets;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketNewMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public TicketMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Message on Ticket: #' . $this->ticket->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.new_message',
            with: [
                'ticket' => $this->ticket,
                'message' => $this->message,
            ],
        );
    }
}
