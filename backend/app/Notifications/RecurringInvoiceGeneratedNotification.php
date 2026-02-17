<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\RecurringInvoiceProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringInvoiceGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RecurringInvoiceProfile $profile,
        private readonly Invoice $invoice,
    ) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $preferences = $notifiable->notification_preferences['recurring_invoice_generated'] ?? [
            'email' => true,
            'in_app' => true,
        ];

        $channels = [];

        if (($preferences['email'] ?? false) === true) {
            $channels[] = 'mail';
        }

        if (($preferences['in_app'] ?? false) === true) {
            $channels[] = 'database';
        }

        return $channels === [] ? ['database'] : $channels;
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recurring invoice generated: '.$this->invoice->number)
            ->line('A recurring invoice has been generated from profile "'.$this->profile->name.'".')
            ->line('Invoice number: '.$this->invoice->number)
            ->line('Total: '.number_format((float) $this->invoice->total, 2).' '.$this->invoice->currency);
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'recurring_invoice_generated',
            'profile_id' => $this->profile->id,
            'profile_name' => $this->profile->name,
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'invoice_total' => (float) $this->invoice->total,
            'currency' => $this->invoice->currency,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
