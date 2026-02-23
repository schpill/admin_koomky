<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignTest extends TestCase
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
        $this->ticket = Ticket::factory()->for($this->owner, 'owner')->create(['assigned_to' => $this->assignee->id]);
    }

    /** @test */
    public function owner_can_assign_ticket_to_any_user()
    {
        $newAssignee = User::factory()->create();
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/assign', [
            'assigned_to' => $newAssignee->id,
        ]);
        $response->assertOk();
        $this->assertEquals($newAssignee->id, $this->ticket->fresh()->assigned_to);
    }

    /** @test */
    public function assignee_can_assign_ticket_to_themselves()
    {
        // Ticket is already assigned to $this->assignee
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/assign', [
            'assigned_to' => $this->assignee->id,
        ]);
        $response->assertOk();
        $this->assertEquals($this->assignee->id, $this->ticket->fresh()->assigned_to);
    }

    /** @test */
    public function assignee_cannot_assign_ticket_to_other_user()
    {
        $anotherUser = User::factory()->create();
        $response = $this->actingAs($this->assignee, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/assign', [
            'assigned_to' => $anotherUser->id,
        ]);
        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function non_owner_cannot_assign_ticket()
    {
        $newAssignee = User::factory()->create();
        $response = $this->actingAs($this->otherUser, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/assign', [
            'assigned_to' => $newAssignee->id,
        ]);
        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function assigning_to_non_existent_user_returns_422()
    {
        $nonExistentUuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'; // Example UUID
        $response = $this->actingAs($this->owner, 'sanctum')->patchJson('/api/v1/tickets/'.$this->ticket->id.'/assign', [
            'assigned_to' => $nonExistentUuid,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assigned_to']);
    }
}
