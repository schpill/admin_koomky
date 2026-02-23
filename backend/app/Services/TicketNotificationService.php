<?php

namespace App\Services;

use App\Mail\Tickets\TicketAssigned;
use App\Mail\Tickets\TicketClosed;
use App\Mail\Tickets\TicketNewMessage;
use App\Mail\Tickets\TicketResolved;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Mail;

class TicketNotificationService
{
    public function notifyAssigned(Ticket $ticket): void
    {
        // Placeholder for sending notification when a ticket is assigned
        Mail::to($ticket->assignee)->queue(new TicketAssigned($ticket));
    }

    public function notifyOwnerResolved(Ticket $ticket): void
    {
        // Placeholder for sending notification when a ticket is resolved
        Mail::to($ticket->owner)->queue(new TicketResolved($ticket));
    }

    public function notifyOwnerClosed(Ticket $ticket): void
    {
        // Placeholder for sending notification when a ticket is closed
        Mail::to($ticket->owner)->queue(new TicketClosed($ticket));
    }

    public function notifyParticipantsNewMessage(Ticket $ticket, TicketMessage $message): void
    {
        if ($message->is_internal) {
            return; // Do not send notifications for internal messages
        }

        $recipients = collect([$ticket->owner, $ticket->assignee])
            ->filter(fn ($user) => $user && $user->id !== $message->user_id)
            ->unique('id');

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new TicketNewMessage($ticket, $message));
        }
    }
}
