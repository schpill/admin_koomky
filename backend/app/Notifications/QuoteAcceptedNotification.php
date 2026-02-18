<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Quote $quote,
        private readonly string $decision,
        private readonly ?string $reason = null,
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
        $message = (new MailMessage)
            ->subject('Quote '.$this->quote->number.' '.$this->decision)
            ->line('Quote '.$this->quote->number.' was '.$this->decision.' by your client.');

        if ($this->reason !== null && $this->reason !== '') {
            $message->line('Reason: '.$this->reason);
        }

        return $message;
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'quote_'.$this->decision,
            'quote_id' => $this->quote->id,
            'quote_number' => $this->quote->number,
            'decision' => $this->decision,
            'reason' => $this->reason,
            'at' => now()->toDateTimeString(),
        ];
    }
}
