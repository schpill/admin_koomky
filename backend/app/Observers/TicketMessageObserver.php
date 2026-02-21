<?php

namespace App\Observers;

use App\Models\TicketMessage;
use App\Services\TicketNotificationService;

class TicketMessageObserver
{
    public function __construct(protected TicketNotificationService $notificationService)
    {
    }

    /**
     * Handle the TicketMessage "created" event.
     */
    public function created(TicketMessage $ticketMessage): void
    {
        $ticket = $ticketMessage->ticket;

        // Set first_response_at if it's the first public message from an assignee
        if (
            ! $ticketMessage->is_internal &&
            is_null($ticket->first_response_at) &&
            $ticketMessage->user_id === $ticket->assigned_to
        ) {
            $ticket->first_response_at = now();
            $ticket->save();
        }

        // Trigger notification for public messages
        $this->notificationService->notifyParticipantsNewMessage($ticket, $ticketMessage);
    }

    /**
     * Handle the TicketMessage "updated" event.
     */
    public function updated(TicketMessage $ticketMessage): void
    {
        //
    }

    /**
     * Handle the TicketMessage "deleted" event.
     */
    public function deleted(TicketMessage $ticketMessage): void
    {
        //
    }

    /**
     * Handle the TicketMessage "restored" event.
     */
    public function restored(TicketMessage $ticketMessage): void
    {
        //
    }

    /**
     * Handle the TicketMessage "force deleted" event.
     */
    public function forceDeleted(TicketMessage $ticketMessage): void
    {
        //
    }
}
