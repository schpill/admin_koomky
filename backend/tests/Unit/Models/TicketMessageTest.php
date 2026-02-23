<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_ticket_message_can_be_created_by_factory()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $message = TicketMessage::factory()->for($ticket)->create();
        $this->assertNotNull($message);
        $this->assertInstanceOf(TicketMessage::class, $message);
        $this->assertNotNull($message->ticket);
        $this->assertNotNull($message->user);
    }

    /** @test */
    public function is_public_scope_returns_only_public_messages()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        TicketMessage::factory()->for($ticket)->public()->create();
        TicketMessage::factory()->for($ticket)->internal()->create();

        $publicMessages = TicketMessage::isPublic()->get();

        $this->assertCount(1, $publicMessages);
        $this->assertFalse($publicMessages->first()->is_internal);
    }

    /** @test */
    public function is_internal_scope_returns_only_internal_messages()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        TicketMessage::factory()->for($ticket)->public()->create();
        TicketMessage::factory()->for($ticket)->internal()->create();

        $internalMessages = TicketMessage::isInternal()->get();

        $this->assertCount(1, $internalMessages);
        $this->assertTrue($internalMessages->first()->is_internal);
    }

    /** @test */
    public function a_ticket_message_belongs_to_a_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $message = TicketMessage::factory()->for($ticket)->create();

        $this->assertInstanceOf(Ticket::class, $message->ticket);
        $this->assertEquals($ticket->id, $message->ticket->id);
    }
}
