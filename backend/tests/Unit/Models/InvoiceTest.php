<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('invoice factory creates valid model', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice->id)->toBeString();
    expect($invoice->number)->toMatch('/^FAC-\d{4}-\d{4}$/');
    expect($invoice->status)->toBeString();
});

test('invoice relationships are configured', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_id' => $project->id,
    ]);

    $lineItem = LineItem::factory()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
    ]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 100,
    ]);

    expect($invoice->user)->toBeInstanceOf(User::class);
    expect($invoice->client)->toBeInstanceOf(Client::class);
    expect($invoice->project)->toBeInstanceOf(Project::class);
    expect($invoice->lineItems->first()?->id)->toBe($lineItem->id);
    expect($invoice->payments->first()?->id)->toBe($payment->id);
});

test('invoice scopes filter by status client and date range', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'status' => 'sent',
        'issue_date' => now()->subDays(5)->toDateString(),
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
        'status' => 'draft',
        'issue_date' => now()->toDateString(),
    ]);

    $byStatus = Invoice::query()->byStatus('sent')->get();
    $byClient = Invoice::query()->byClient($clientA->id)->get();
    $byDateRange = Invoice::query()->byDateRange(
        now()->subDays(7)->toDateString(),
        now()->subDays(1)->toDateString()
    )->get();

    expect($byStatus)->toHaveCount(1);
    expect($byClient)->toHaveCount(1);
    expect($byDateRange)->toHaveCount(1);
});

test('invoice overdue scope returns sent and viewed invoices past due date', function () {
    Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    Invoice::factory()->create([
        'status' => 'viewed',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    Invoice::factory()->create([
        'status' => 'paid',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $overdue = Invoice::query()->overdue()->get();

    expect($overdue)->toHaveCount(2);
});

test('invoice computes balance due from total and payments', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'sent',
        'total' => 1200,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 300,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 200,
    ]);

    $invoice->refresh();

    expect((float) $invoice->amount_paid)->toBe(500.0);
    expect((float) $invoice->balance_due)->toBe(700.0);
});
