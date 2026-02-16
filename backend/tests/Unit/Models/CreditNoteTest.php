<?php

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('credit note factory creates valid model', function () {
    $creditNote = CreditNote::factory()->create();

    expect($creditNote->id)->toBeString();
    expect($creditNote->number)->toMatch('/^AVO-\d{4}-\d{4}$/');
    expect($creditNote->status)->toBeString();
});

test('credit note relationships are configured', function () {
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
    ]);

    $lineItem = LineItem::factory()->create([
        'documentable_type' => CreditNote::class,
        'documentable_id' => $creditNote->id,
    ]);

    expect($creditNote->user)->toBeInstanceOf(User::class);
    expect($creditNote->client)->toBeInstanceOf(Client::class);
    expect($creditNote->invoice)->toBeInstanceOf(Invoice::class);
    expect($creditNote->lineItems->first()?->id)->toBe($lineItem->id);
});

test('credit note scopes filter by status client and invoice', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    $invoiceA = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
    ]);

    $invoiceB = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
    ]);

    CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'invoice_id' => $invoiceA->id,
        'status' => 'sent',
    ]);

    CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
        'invoice_id' => $invoiceB->id,
        'status' => 'draft',
    ]);

    expect(CreditNote::query()->byStatus('sent')->count())->toBe(1);
    expect(CreditNote::query()->byClient($clientA->id)->count())->toBe(1);
    expect(CreditNote::query()->byInvoice($invoiceA->id)->count())->toBe(1);
});
