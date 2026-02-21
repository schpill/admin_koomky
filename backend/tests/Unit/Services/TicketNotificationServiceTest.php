<?php

namespace Tests\Unit\Services;

use App\Mail\Tickets\TicketAssigned;
use App\Mail\Tickets\TicketClosed;
use App\Mail\Tickets\TicketNewMessage;
use App\Mail\Tickets\TicketResolved;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TicketNotificationService $service;
    protected User $owner;
    protected User $assignee;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketNotificationService();
        $this->owner = User::factory()->create();
        $this->assignee = User::factory()->create();
        $this->ticket = Ticket::factory()->for($this->owner, 'owner')->create([
            'assigned_to' => $this->assignee->id,
        ]);

        Mail::fake();
    }

    /** @test */
    public function notifyAssigned_queues_mail_to_assignee()
    {
        $this->service->notifyAssigned($this->ticket);

        Mail::assertQueued(TicketAssigned::class, function ($mail) {
            return $mail->hasTo($this->assignee->email)
                && $mail->ticket->id === $this->ticket->id;
        });
    }

    /** @test */
    public function notifyOwnerResolved_queues_mail_to_owner()
    {
        $this->service->notifyOwnerResolved($this->ticket);

        Mail::assertQueued(TicketResolved::class, function ($mail) {
            return $mail->hasTo($this->owner->email)
                && $mail->ticket->id === $this->ticket->id;
        });
    }

    /** @test */
    public function notifyOwnerClosed_queues_mail_to_owner()
    {
        $this->service->notifyOwnerClosed($this->ticket);

        Mail::assertQueued(TicketClosed::class, function ($mail) {
            return $mail->hasTo($this->owner->email)
                && $mail->ticket->id === $this->ticket->id;
        });
    }

    /** @test */
    public function notifyParticipantsNewMessage_queues_mail_to_owner_and_assignee_if_not_author()
    {
        $messageAuthor = User::factory()->create();
        $message = TicketMessage::factory()->for($this->ticket)->for($messageAuthor, 'user')->public()->create();

        // Ensure the message author is neither the owner nor the assignee for this test scenario
        // This might need adjustment based on specific test data, but for now it's a simple setup
        $this->assertNotEquals($messageAuthor->id, $this->owner->id);
        $this->assertNotEquals($messageAuthor->id, $this->assignee->id);

        $this->service->notifyParticipantsNewMessage($this->ticket, $message);

        Mail::assertQueued(TicketNewMessage::class, 2, function ($mail) use ($message) {
            // The mail should be queued for both owner and assignee
            return ($mail->hasTo($this->owner->email) || $mail->hasTo($this->assignee->email))
                && $mail->ticket->id === $this->ticket->id
                && $mail->message->id === $message->id;
        });
    }

    /** @test */
    public function notifyParticipantsNewMessage_does_not_queue_mail_to_author()
    {
        $message = TicketMessage::factory()->for($this->ticket)->for($this->owner, 'user')->public()->create();

        $this->service->notifyParticipantsNewMessage($this->ticket, $message);

        Mail::assertNotQueued(TicketNewMessage::class, function ($mail) {
            return $mail->hasTo($this->owner->email);
        });
    }

    /** @test */
    public function notifyParticipantsNewMessage_does_not_queue_mail_for_internal_messages()
    {
        $message = TicketMessage::factory()->for($this->ticket)->for($this->owner, 'user')->internal()->create();

        $this->service->notifyParticipantsNewMessage($this->ticket, $message);

        Mail::assertNotQueued(TicketNewMessage::class);
    }
}
