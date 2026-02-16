<?php

use App\Jobs\SendCreditNoteJob;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sending credit note dispatches queued job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'billing@client.test',
    ]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credit-notes/'.$creditNote->id.'/send');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'sent');

    Queue::assertPushed(SendCreditNoteJob::class, function (SendCreditNoteJob $job) use ($creditNote) {
        return $job->creditNoteId === $creditNote->id;
    });
});
