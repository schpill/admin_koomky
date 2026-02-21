<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_ticket_can_be_created_by_factory_and_assigned_to_defaults_to_owner()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => null]);

        $this->assertNotNull($ticket);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($owner->id, $ticket->owner->id);
        $this->assertEquals($owner->id, $ticket->assigned_to);
    }
}
