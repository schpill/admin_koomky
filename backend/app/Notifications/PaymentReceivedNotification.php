<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\PaymentIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment received for invoice '.$this->invoice->number)
            ->line('A client has paid invoice '.$this->invoice->number.'.')
            ->line('Amount: '.number_format((float) $this->paymentIntent->amount, 2).' '.$this->paymentIntent->currency);
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_received',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'payment_intent_id' => $this->paymentIntent->id,
            'amount' => (float) $this->paymentIntent->amount,
            'currency' => $this->paymentIntent->currency,
        ];
    }
}
