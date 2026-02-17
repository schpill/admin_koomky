<?php

namespace App\Notifications;

use App\Models\Campaign;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Campaign $campaign) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $preferences = $notifiable->notification_preferences['campaign_completed'] ?? [
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
            ->subject('Campaign completed: '.$this->campaign->name)
            ->line('Your campaign "'.$this->campaign->name.'" has completed processing.')
            ->line('Status: '.$this->campaign->status);
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $completedAt = $this->campaign->getAttribute('completed_at');

        return [
            'type' => 'campaign_completed',
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'status' => $this->campaign->status,
            'completed_at' => $completedAt instanceof CarbonInterface ? $completedAt->toDateTimeString() : (is_string($completedAt) && $completedAt !== '' ? $completedAt : null),
        ];
    }
}
