<?php

use App\Models\Client;
use App\Models\LineItem;
use App\Models\Quote;
use App\Models\User;
use App\Services\ConvertQuoteToInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('convert quote to invoice copies line items and links records', function () {
    $user = User::factory()->create([
        'payment_terms_days' => 45,
    ]);
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
        'discount_type' => 'percentage',
        'discount_value' => 5,
    ]);

    LineItem::factory()->create([
        'documentable_type' => Quote::class,
        'documentable_id' => $quote->id,
        'description' => 'Discovery',
        'quantity' => 2,
        'unit_price' => 120,
        'vat_rate' => 20,
    ]);

    LineItem::factory()->create([
        'documentable_type' => Quote::class,
        'documentable_id' => $quote->id,
        'description' => 'Implementation',
        'quantity' => 5,
        'unit_price' => 80,
        'vat_rate' => 10,
    ]);

    $service = app(ConvertQuoteToInvoiceService::class);

    $invoice = $service->convert($quote);

    $quote->refresh();
    $invoice->load('lineItems');

    expect($invoice->number)->toMatch('/^FAC-\d{4}-\d{4}$/');
    expect($invoice->status)->toBe('draft');
    expect($invoice->lineItems)->toHaveCount(2);
    expect($invoice->lineItems->pluck('description')->all())->toBe([
        'Discovery',
        'Implementation',
    ]);
    expect($quote->converted_invoice_id)->toBe($invoice->id);
    expect($quote->status)->toBe('accepted');
    expect($quote->accepted_at)->not()->toBeNull();
});
