<?php

namespace Tests\Unit\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DataExportService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(DataExportService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_includes_tickets_in_the_export()
    {
        $ticket = Ticket::factory()->for($this->user, 'owner')->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('tickets', $exportData);
        $this->assertCount(1, $exportData['tickets']);
        $this->assertEquals($ticket->id, $exportData['tickets'][0]['id']);
    }

    /** @test */
    public function it_includes_public_ticket_messages_in_the_export()
    {
        $ticket = Ticket::factory()->for($this->user, 'owner')->create();
        $publicMessage = TicketMessage::factory()->for($ticket)->for($this->user, 'user')->public()->create();
        $internalMessage = TicketMessage::factory()->for($ticket)->for($this->user, 'user')->internal()->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('ticket_messages', $exportData);
        $this->assertCount(1, $exportData['ticket_messages']);
        $this->assertEquals($publicMessage->id, $exportData['ticket_messages'][0]['id']);
        $this->assertNotContains($internalMessage->id, array_column($exportData['ticket_messages'], 'id'));
    }

    /** @test */
    public function it_does_not_include_other_users_tickets()
    {
        $otherUser = User::factory()->create();
        Ticket::factory()->for($otherUser, 'owner')->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('tickets', $exportData);
        $this->assertEmpty($exportData['tickets']);
    }

    /** @test */
    public function it_does_not_include_other_users_ticket_messages()
    {
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($otherUser, 'owner')->create();
        TicketMessage::factory()->for($ticket)->for($otherUser, 'user')->public()->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('ticket_messages', $exportData);
        $this->assertEmpty($exportData['ticket_messages']);
    }
}
