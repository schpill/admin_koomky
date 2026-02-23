<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\TicketNotificationService;
use App\Services\WebhookDispatchService;

class TicketObserver
{
    public function __construct(
        protected WebhookDispatchService $webhookDispatchService,
        protected TicketNotificationService $ticketNotificationService
    ) {}

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        if (is_null($ticket->assigned_to)) {
            // Use updateQuietly to avoid re-triggering model events
            $ticket->updateQuietly(['assigned_to' => $ticket->user_id]);
        }

        $this->webhookDispatchService->dispatch('ticket.opened', $ticket->toArray(), $ticket->user_id);
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Dispatch webhook on assigned_to change and notify assignee
        if ($ticket->isDirty('assigned_to') && ! is_null($ticket->assigned_to)) {
            $this->webhookDispatchService->dispatch('ticket.assigned', $ticket->toArray(), $ticket->user_id);
            $this->ticketNotificationService->notifyAssigned($ticket);
        }

        // Set resolved_at and dispatch webhook when status changes to resolved
        if ($ticket->isDirty('status') && $ticket->status->value === \App\Enums\TicketStatus::Resolved->value) {
            // Use updateQuietly to avoid re-triggering model events from within the observer
            $ticket->updateQuietly(['resolved_at' => now()]);
            $this->webhookDispatchService->dispatch('ticket.resolved', $ticket->toArray(), $ticket->user_id);
            $this->ticketNotificationService->notifyOwnerResolved($ticket);
        }

        // Set closed_at and dispatch webhook when status changes to closed
        if ($ticket->isDirty('status') && $ticket->status->value === \App\Enums\TicketStatus::Closed->value) {
            // Use updateQuietly to avoid re-triggering model events from within the observer
            $ticket->updateQuietly(['closed_at' => now()]);
            $this->webhookDispatchService->dispatch('ticket.closed', $ticket->toArray(), $ticket->user_id);
            $this->ticketNotificationService->notifyOwnerClosed($ticket);
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        $this->webhookDispatchService->dispatch('ticket.deleted', $ticket->toArray(), $ticket->user_id);
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        // Not explicitly required by spec, keep as placeholder for future
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        // Not explicitly required by spec, keep as placeholder for future
    }
}
