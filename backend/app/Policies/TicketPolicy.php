<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view the list of tickets
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create tickets
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id; // Only owner can update the ticket
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id; // Only owner can delete the ticket
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id; // Only owner can assign the ticket
    }

    public function addMessage(User $user, Ticket $ticket): bool
    {
        // Owner or assignee can add messages
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }

    public function uploadDocument(User $user, Ticket $ticket): bool
    {
        // Owner or assignee can upload documents
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }

    public function attachDocument(User $user, Ticket $ticket): bool
    {
        // Owner or assignee can attach existing documents
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }

    public function detachDocument(User $user, Ticket $ticket): bool
    {
        // Owner or assignee can detach documents
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }


    public function changeStatus(User $user, Ticket $ticket): bool
    {
        // Owner or assignee can change status
        return $user->id === $ticket->user_id || $user->id === $ticket->assigned_to;
    }

}
