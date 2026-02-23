<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $assignee;

    protected User $otherUser;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->assignee = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->ticket = Ticket::factory()->for($this->owner, 'owner')->create(['assigned_to' => $this->assignee->id, 'status' => TicketStatus::Open]);
    }

    /** @test */
    public function owner_can_transition_valid_status()
    {
        // Open -> InProgress
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::InProgress->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::InProgress, $this->ticket->fresh()->status);

        // InProgress -> Resolved
        $this->ticket->status = TicketStatus::InProgress;
        $this->ticket->save();
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Resolved->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::Resolved, $this->ticket->fresh()->status);

        // Resolved -> Closed (owner only)
        $this->ticket->status = TicketStatus::Resolved;
        $this->ticket->save();
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Closed->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::Closed, $this->ticket->fresh()->status);

        // Closed -> Open (reopen by owner)
        $this->ticket->status = TicketStatus::Closed;
        $this->ticket->save();
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Open->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::Open, $this->ticket->fresh()->status);
    }

    /** @test */
    public function assignee_can_transition_valid_status()
    {
        // Open -> InProgress
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::InProgress->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::InProgress, $this->ticket->fresh()->status);

        // InProgress -> Resolved
        $this->ticket->status = TicketStatus::InProgress;
        $this->ticket->save();
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Resolved->value,
        ]);
        $response->assertOk();
        $this->assertEquals(TicketStatus::Resolved, $this->ticket->fresh()->status);
    }

    /** @test */
    public function invalid_status_transitions_return_422()
    {
        // Open -> Closed (assignee cannot close)
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Closed->value,
        ]);
        $response->assertStatus(422);

        // Closed -> Resolved (only owner can reopen)
        $this->ticket->status = TicketStatus::Closed;
        $this->ticket->save();
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Resolved->value,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function non_owner_cannot_close_or_reopen_ticket()
    {
        // Assignee tries to close
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Closed->value,
        ]);
        $response->assertStatus(422);

        // Other user tries to close
        $response = $this->actingAs($this->otherUser, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Closed->value,
        ]);
        $response->assertStatus(403); // Forbidden by policy

        // Setup for reopen test
        $this->ticket->status = TicketStatus::Closed;
        $this->ticket->save();

        // Assignee tries to reopen
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Open->value,
        ]);
        $response->assertStatus(422);

        // Other user tries to reopen
        $response = $this->actingAs($this->otherUser, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/status', [
            'status' => TicketStatus::Open->value,
        ]);
        $response->assertStatus(403); // Forbidden by policy
    }
}
