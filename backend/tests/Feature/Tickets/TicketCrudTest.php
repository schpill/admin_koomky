<?php

namespace Tests\Feature\Tickets;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function ticket_creation_requires_a_title()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets', [
            'title' => '',
            'description' => 'A description',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function ticket_creation_requires_a_description()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets', [
            'title' => 'A Title',
            'description' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function ticket_creation_requires_a_future_deadline_if_provided()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets', [
            'title' => 'A Title',
            'description' => 'A description',
            'deadline' => '2020-01-01',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deadline']);
    }

    /** @test */
    public function an_authenticated_user_can_access_ticket_index()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets');
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'filters',
            'data',
        ]);
    }

    /** @test */
    public function an_authenticated_user_can_create_a_ticket()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description.',
            'user_id' => $this->user->id, // Owner
            'priority' => 'normal',
            // other fields will be added later when requests are implemented
        ]);
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Ticket created']);
    }

    /** @test */
    public function an_authenticated_user_can_view_a_ticket()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/'.$ticket->id);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket details']);
    }

    /** @test */
    public function an_authenticated_user_can_update_a_ticket()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->create();
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/'.$ticket->id, [
            'title' => 'Updated Ticket Title',
        ]);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket updated']);
    }

    /** @test */
    public function ticket_update_allows_optional_fields_and_validates_them()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->create();

        // Test invalid category
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/'.$ticket->id, [
            'category' => str_repeat('a', 101),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);

        // Test invalid deadline (past date)
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/'.$ticket->id, [
            'deadline' => '2020-01-01',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deadline']);

        // Test invalid priority
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/'.$ticket->id, [
            'priority' => 'invalid-priority',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);

        // Test valid partial update
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/'.$ticket->id, [
            'title' => 'Partially Updated Title',
        ]);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket updated']);
    }

    /** @test */
    public function an_authenticated_user_can_delete_a_ticket()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/tickets/'.$ticket->id);
        $response->assertStatus(204);
    }

    /** @test */
    public function an_authenticated_user_can_change_ticket_status()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->status(\App\Enums\TicketStatus::Open)->create();
        $response = $this->actingAs($this->user, 'sanctum')->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'in_progress',
        ]);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket status changed']);
    }

    /** @test */
    public function an_authenticated_user_can_assign_a_ticket()
    {
        $ticket = \App\Models\Ticket::factory()->for($this->user, 'owner')->create();
        $assignee = User::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->patchJson('/api/v1/tickets/'.$ticket->id.'/assign', [
            'assigned_to' => $assignee->id,
        ]);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket assigned']);
    }

    /** @test */
    public function an_authenticated_user_can_access_ticket_stats()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/stats');
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket statistics']);
    }

    /** @test */
    public function an_authenticated_user_can_access_overdue_tickets()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/overdue');
        $response->assertOk();
        $response->assertJson(['message' => 'Overdue tickets']);
    }
}
