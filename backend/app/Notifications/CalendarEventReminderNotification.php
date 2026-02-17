<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CalendarEventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CalendarEvent $event) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $preferences = $notifiable->notification_preferences['calendar_event_reminder'] ?? [
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
            ->subject('Calendar reminder: '.$this->event->title)
            ->line('An event requires your attention: '.$this->event->title)
            ->line('Starts at: '.$this->event->start_at->toDateTimeString());
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'calendar_event_reminder',
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'start_at' => $this->event->start_at->toDateTimeString(),
            'event_type' => $this->event->type,
        ];
    }
}
