<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice pdf service can batch generate multiple invoices', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoices = Invoice::factory()
        ->count(2)
        ->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => 'draft',
        ]);

    foreach ($invoices as $invoice) {
        LineItem::factory()->create([
            'documentable_type' => Invoice::class,
            'documentable_id' => $invoice->id,
            'description' => 'Batch test line item',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 20,
            'total' => 120,
        ]);
    }

    $service = app(InvoicePdfService::class);
    $pdfs = $service->generateBatch($invoices->all());

    expect($pdfs)->toHaveCount(2);
    expect(array_key_exists((string) $invoices[0]->id, $pdfs))->toBeTrue();
    expect(array_key_exists((string) $invoices[1]->id, $pdfs))->toBeTrue();
    expect($pdfs[(string) $invoices[0]->id])->toStartWith('%PDF');
    expect($pdfs[(string) $invoices[1]->id])->toStartWith('%PDF');
});
