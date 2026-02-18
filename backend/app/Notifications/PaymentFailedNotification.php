<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\PaymentIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly PaymentIntent $paymentIntent,
    ) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment failed for invoice '.$this->invoice->number)
            ->line('Your payment for invoice '.$this->invoice->number.' failed.')
            ->line('Reason: '.((string) ($this->paymentIntent->failure_reason ?? 'Unknown error')))
            ->line('Please retry with another payment method.');
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_failed',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'payment_intent_id' => $this->paymentIntent->id,
            'failure_reason' => $this->paymentIntent->failure_reason,
        ];
    }
}
