<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function an_authenticated_user_can_access_ticket_stats()
    {
        // Create some tickets for the user with deterministic status and priority
        Ticket::factory()->for($this->user, 'owner')->count(3)->status(TicketStatus::Open)->priority(TicketPriority::Normal)->create();
        Ticket::factory()->for($this->user, 'owner')->count(2)->status(TicketStatus::InProgress)->priority(TicketPriority::High)->create();
        Ticket::factory()->for($this->user, 'owner')->count(1)->status(TicketStatus::Resolved)->priority(TicketPriority::Low)->create();
        Ticket::factory()->for($this->user, 'owner')->count(1)->status(TicketStatus::Closed)->priority(TicketPriority::Urgent)->create();
        Ticket::factory()->for($this->user, 'owner')->overdue()->priority(TicketPriority::Normal)->count(1)->create(); // Overdue implies Open status

        // Create some resolved tickets with resolved_at for average calculation
        // These also count towards resolved and total tickets
        Ticket::factory()->for($this->user, 'owner')->resolved()->priority(TicketPriority::Normal)->create(['created_at' => now()->subDays(5), 'resolved_at' => now()->subDays(3)]);
        Ticket::factory()->for($this->user, 'owner')->resolved()->priority(TicketPriority::Normal)->create(['created_at' => now()->subDays(10), 'resolved_at' => now()->subDays(5)]);

        // Expected counts:
        // Total tickets: 3 + 2 + 1 + 1 + 1 + 2 = 10
        // Open: 3 (from first batch) + 1 (overdue) = 4
        // InProgress: 2
        // Resolved: 1 (from first batch) + 2 (for avg) = 3
        // Closed: 1
        // Low priority: 1
        // Normal priority: 3 + 1 + 2 = 6
        // High priority: 2
        // Urgent priority: 1
        // Overdue: 1
        // Avg resolution time: (2 days + 5 days) / 2 tickets = (48 + 120) / 2 = 168 / 2 = 84 hours (approx)

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'total_tickets',
                'total_open',
                'total_in_progress',
                'total_pending',
                'total_resolved',
                'total_closed',
                'total_low_priority',
                'total_normal_priority',
                'total_high_priority',
                'total_urgent_priority',
                'total_overdue',
                'average_resolution_time_in_hours',
            ],
        ]);

        $response->assertJson(['data' => [
            'total_tickets' => 10,
            'total_open' => 4,
            'total_in_progress' => 2,
            'total_pending' => 0, // No specific pending tickets created
            'total_resolved' => 3,
            'total_closed' => 1,
            'total_low_priority' => 1,
            'total_normal_priority' => 6,
            'total_high_priority' => 2,
            'total_urgent_priority' => 1,
            'total_overdue' => 1,
            'average_resolution_time_in_hours' => 84.00, // Assert with a small delta if needed due to float precision
        ]]);

        // Assert average resolution time is a numeric value (approx. 72 hours for the 2 resolved tickets)
        $this->assertIsNumeric($response->json('data.average_resolution_time_in_hours'));
        $this->assertGreaterThan(0, $response->json('data.average_resolution_time_in_hours'));
    }

    /** @test */
    public function an_authenticated_user_without_tickets_gets_zero_stats()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/stats');

        $response->assertOk();
        $response->assertJson(['data' => [
            'total_tickets' => 0,
            'total_open' => 0,
            'total_in_progress' => 0,
            'total_pending' => 0,
            'total_resolved' => 0,
            'total_closed' => 0,
            'total_low_priority' => 0,
            'total_normal_priority' => 0,
            'total_high_priority' => 0,
            'total_urgent_priority' => 0,
            'total_overdue' => 0,
            'average_resolution_time_in_hours' => 0,
        ]]);
    }
}
