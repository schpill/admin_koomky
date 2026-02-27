<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\ReminderStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly ReminderStep $step,
        public readonly ?string $payLink = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->interpolate($this->step->subject),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reminder.invoice-reminder',
            with: [
                'invoice' => $this->invoice,
                'body' => nl2br(e($this->interpolate($this->step->body))),
                'payLink' => $this->payLink,
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    public function attachments(): array
    {
        return [];
    }

    private function interpolate(string $template): string
    {
        $clientName = (string) ($this->invoice->client?->name ?? 'Client');
        $daysOverdue = max(0, $this->invoice->due_date->startOfDay()->diffInDays(now()->startOfDay(), false));

        $replacements = [
            '{{client_name}}' => $clientName,
            '{{invoice_number}}' => (string) $this->invoice->number,
            '{{invoice_amount}}' => number_format((float) $this->invoice->total, 2, '.', ' ').' '.(string) $this->invoice->currency,
            '{{due_date}}' => $this->invoice->due_date->toDateString(),
            '{{days_overdue}}' => (string) $daysOverdue,
            '{{pay_link}}' => (string) ($this->payLink ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
