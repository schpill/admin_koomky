<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('quote factory creates valid model', function () {
    $quote = Quote::factory()->create();

    expect($quote->id)->toBeString();
    expect($quote->number)->toMatch('/^DEV-\d{4}-\d{4}$/');
    expect($quote->status)->toBeString();
});

test('quote relationships are configured', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'converted_invoice_id' => $invoice->id,
    ]);

    $lineItem = LineItem::factory()->create([
        'documentable_type' => Quote::class,
        'documentable_id' => $quote->id,
    ]);

    expect($quote->user)->toBeInstanceOf(User::class);
    expect($quote->client)->toBeInstanceOf(Client::class);
    expect($quote->project)->toBeInstanceOf(Project::class);
    expect($quote->convertedInvoice)->toBeInstanceOf(Invoice::class);
    expect($quote->lineItems->first()?->id)->toBe($lineItem->id);
});

test('quote scopes filter by status client and date range', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'status' => 'sent',
        'issue_date' => now()->subDays(5)->toDateString(),
    ]);

    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $clientB->id,
        'status' => 'draft',
        'issue_date' => now()->toDateString(),
    ]);

    $byStatus = Quote::query()->byStatus('sent')->get();
    $byClient = Quote::query()->byClient($clientA->id)->get();
    $byDateRange = Quote::query()->byDateRange(
        now()->subDays(7)->toDateString(),
        now()->subDays(1)->toDateString()
    )->get();

    expect($byStatus)->toHaveCount(1);
    expect($byClient)->toHaveCount(1);
    expect($byDateRange)->toHaveCount(1);
});

test('quote status transitions are enforced', function () {
    $quote = Quote::factory()->create(['status' => 'draft']);

    expect($quote->canTransitionTo('sent'))->toBeTrue();
    expect($quote->canTransitionTo('accepted'))->toBeFalse();

    $quote->status = 'sent';
    expect($quote->canTransitionTo('accepted'))->toBeTrue();
    expect($quote->canTransitionTo('rejected'))->toBeTrue();
    expect($quote->canTransitionTo('expired'))->toBeTrue();

    $quote->status = 'accepted';
    expect($quote->canTransitionTo('sent'))->toBeFalse();
});
