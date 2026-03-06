<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\RagUsageLog;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DataExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DataExportService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
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

    /** @test */
    public function it_includes_document_chunks_in_the_export()
    {
        $document = Document::factory()->for($this->user)->create();
        $chunk = DocumentChunk::factory()
            ->for($document, 'document')
            ->for($this->user, 'user')
            ->create(['chunk_index' => 0]);

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('document_chunks', $exportData);
        $this->assertCount(1, $exportData['document_chunks']);
        $this->assertEquals($chunk->id, $exportData['document_chunks'][0]['id']);
    }

    /** @test */
    public function it_does_not_include_other_users_document_chunks()
    {
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($otherUser)->create();
        DocumentChunk::factory()->for($document, 'document')->for($otherUser, 'user')->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('document_chunks', $exportData);
        $this->assertEmpty($exportData['document_chunks']);
    }

    /** @test */
    public function it_includes_rag_usage_logs_in_the_export()
    {
        $log = RagUsageLog::factory()->for($this->user)->create([
            'question' => 'Quel est le délai de paiement ?',
        ]);

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('rag_usage_logs', $exportData);
        $this->assertCount(1, $exportData['rag_usage_logs']);
        $this->assertEquals($log->id, $exportData['rag_usage_logs'][0]['id']);
    }

    /** @test */
    public function it_does_not_include_other_users_rag_usage_logs()
    {
        $otherUser = User::factory()->create();
        RagUsageLog::factory()->for($otherUser)->create();

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('rag_usage_logs', $exportData);
        $this->assertEmpty($exportData['rag_usage_logs']);
    }

    /** @test */
    public function it_explicitly_includes_client_industry_and_department_in_export()
    {
        Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Prospect A',
            'industry' => 'Wedding Planner',
            'department' => '60',
            'status' => 'prospect',
        ]);

        $exportData = $this->service->exportUserData($this->user);

        $this->assertArrayHasKey('clients', $exportData);
        $this->assertCount(1, $exportData['clients']);
        $this->assertEquals('Wedding Planner', $exportData['clients'][0]['industry']);
        $this->assertEquals('60', $exportData['clients'][0]['department']);
    }
}
