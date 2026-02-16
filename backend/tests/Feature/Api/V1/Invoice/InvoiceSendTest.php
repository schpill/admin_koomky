<?php

use App\Jobs\SendInvoiceJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sending invoice dispatches queued job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'billing@client.test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/send');

    $response->assertStatus(200);

    Queue::assertPushed(SendInvoiceJob::class, function (SendInvoiceJob $job) use ($invoice) {
        return $job->invoiceId === $invoice->id;
    });
});
