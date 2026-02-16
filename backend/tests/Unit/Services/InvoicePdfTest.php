<?php

use App\Models\Invoice;
use App\Models\LineItem;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('invoice pdf service returns valid pdf binary', function () {
    $invoice = Invoice::factory()->create([
        'number' => 'FAC-2026-0001',
    ]);

    LineItem::factory()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'description' => 'Consulting service',
        'quantity' => 2,
        'unit_price' => 300,
        'vat_rate' => 20,
        'total' => 600,
    ]);

    $invoice->load(['lineItems', 'client', 'user']);

    $service = app(InvoicePdfService::class);
    $pdfBinary = $service->generate($invoice);

    expect($pdfBinary)->toBeString();
    expect(strlen($pdfBinary))->toBeGreaterThan(100);
    expect(substr($pdfBinary, 0, 4))->toBe('%PDF');
});
