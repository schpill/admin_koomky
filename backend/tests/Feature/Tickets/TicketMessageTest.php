<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->ticket = Ticket::factory()->for($this->user, 'owner')->create();
    }

    /** @test */
    public function message_creation_requires_content()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets/' . $this->ticket->id . '/messages', [
            'content' => '',
            'is_internal' => false,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function message_creation_validates_is_internal_as_boolean()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets/' . $this->ticket->id . '/messages', [
            'content' => 'Some content',
            'is_internal' => 'not-a-boolean',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['is_internal']);
    }

    /** @test */
    public function an_authenticated_user_can_list_ticket_messages()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/' . $this->ticket->id . '/messages');
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'ticket_id',
            'data',
        ]);
    }

    /** @test */
    public function an_authenticated_user_can_create_a_ticket_message()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets/' . $this->ticket->id . '/messages', [
            'content' => 'Test message content',
            'is_internal' => false,
        ]);
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Ticket message created']);
    }

    /** @test */
    public function an_authenticated_user_can_update_a_ticket_message()
    {
        $message = TicketMessage::factory()->for($this->ticket)->for($this->user)->create();
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/tickets/' . $this->ticket->id . '/messages/' . $message->id, [
            'content' => 'Updated message content',
        ]);
        $response->assertOk();
        $response->assertJson(['message' => 'Ticket message updated']);
    }

    /** @test */
    public function an_authenticated_user_can_delete_a_ticket_message()
    {
        $message = TicketMessage::factory()->for($this->ticket)->for($this->user)->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/tickets/' . $this->ticket->id . '/messages/' . $message->id);
        $response->assertStatus(204);
    }
}
