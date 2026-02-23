<?php

namespace Tests\Feature\Tickets;

use App\Models\Document;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->ticket = Ticket::factory()->for($this->user, 'owner')->create();

        Storage::fake('public');
    }

    /** @test */
    public function an_authenticated_user_can_list_ticket_documents()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tickets/'.$this->ticket->id.'/documents');
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'ticket_id',
            'data',
        ]);
    }

    /** @test */
    public function an_authenticated_user_can_upload_and_attach_a_document_to_a_ticket()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets/'.$this->ticket->id.'/documents', [
            'document' => $file,
        ]);
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Document uploaded and attached']);
    }

    /** @test */
    public function an_authenticated_user_can_attach_an_existing_document_to_a_ticket()
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tickets/'.$this->ticket->id.'/documents/attach', [
            'document_id' => $document->id,
        ]);
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Existing document attached']);
    }

    /** @test */
    public function an_authenticated_user_can_detach_a_document_from_a_ticket()
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);
        $this->ticket->documents()->attach($document->id, ['attached_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/tickets/'.$this->ticket->id.'/documents/'.$document->id);
        $response->assertStatus(204);
    }
}
