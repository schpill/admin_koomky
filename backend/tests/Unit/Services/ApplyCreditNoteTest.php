<?php

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ApplyCreditNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('apply credit note fully settles invoice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 500,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'total' => 500,
    ]);

    $service = app(ApplyCreditNoteService::class);
    $service->apply($creditNote);

    $invoice->refresh();
    $creditNote->refresh();

    expect($invoice->status)->toBe('paid');
    expect((float) $invoice->amount_paid)->toBe(500.0);
    expect($creditNote->status)->toBe('applied');
    expect($creditNote->applied_at)->not()->toBeNull();
});

test('apply credit note partially reduces invoice balance', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 600,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'total' => 150,
    ]);

    $service = app(ApplyCreditNoteService::class);
    $service->apply($creditNote);

    $invoice->refresh();

    expect($invoice->status)->toBe('partially_paid');
    expect((float) $invoice->amount_paid)->toBe(150.0);
    expect((float) $invoice->balance_due)->toBe(450.0);
});

test('apply credit note rejects amount above remaining balance', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 200,
    ]);

    $invoice->payments()->create([
        'amount' => 180,
        'payment_date' => now()->toDateString(),
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'total' => 50,
    ]);

    $service = app(ApplyCreditNoteService::class);

    expect(fn () => $service->apply($creditNote))
        ->toThrow(RuntimeException::class, 'Credit note total exceeds invoice remaining balance');
});
