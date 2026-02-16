<?php

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validCreditNotePayload(string $invoiceId): array
{
    return [
        'invoice_id' => $invoiceId,
        'issue_date' => now()->toDateString(),
        'currency' => 'EUR',
        'reason' => 'Partial refund',
        'line_items' => [
            [
                'description' => 'Refund line',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 20,
            ],
        ],
    ];
}

test('user can create read update and delete draft credit note', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'total' => 500,
    ]);

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credit-notes', validCreditNotePayload($invoice->id));

    $create->assertStatus(201)
        ->assertJsonPath('data.invoice_id', $invoice->id)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.number', fn (string $number): bool => str_starts_with($number, 'AVO-'));

    $creditNoteId = (string) $create->json('data.id');

    $this->assertDatabaseHas('credit_notes', ['id' => $creditNoteId]);
    $this->assertDatabaseCount('line_items', 1);

    $show = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credit-notes/'.$creditNoteId);

    $show->assertStatus(200)
        ->assertJsonPath('data.id', $creditNoteId);

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/credit-notes/'.$creditNoteId, [
            ...validCreditNotePayload($invoice->id),
            'reason' => 'Updated reason',
        ]);

    $update->assertStatus(200)
        ->assertJsonPath('data.reason', 'Updated reason');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/credit-notes/'.$creditNoteId);

    $delete->assertStatus(200);
    $this->assertDatabaseMissing('credit_notes', ['id' => $creditNoteId]);
});

test('credit note creation rejects totals above invoice balance', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 100,
    ]);

    $invoice->payments()->create([
        'amount' => 90,
        'payment_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credit-notes', [
            'invoice_id' => $invoice->id,
            'issue_date' => now()->toDateString(),
            'line_items' => [
                [
                    'description' => 'Too much',
                    'quantity' => 1,
                    'unit_price' => 20,
                    'vat_rate' => 0,
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Credit note total exceeds invoice remaining balance');
});

test('non draft credit note cannot be deleted', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
    ]);

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/credit-notes/'.$creditNote->id);

    $delete->assertStatus(422)
        ->assertJsonPath('message', 'Only draft credit notes can be deleted');
});
