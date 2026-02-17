<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('invoice csv import creates invoice and line item linked to client reference', function () {
    $user = User::factory()->create();
    Client::factory()->for($user)->create(['reference' => 'CLI-2026-0002']);

    $csv = implode("\n", [
        'client_reference,issue_date,due_date,status,currency,notes,line_item_description,line_item_quantity,line_item_unit_price,line_item_vat_rate',
        'CLI-2026-0002,2026-02-01,2026-02-28,draft,EUR,Imported invoice,Consulting,2,100,20',
    ]);

    $file = UploadedFile::fake()->createWithContent('invoices.csv', $csv);

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/import/invoices', [
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.imported', 1)
        ->assertJsonPath('data.errors', []);

    $invoice = Invoice::query()->where('user_id', $user->id)->first();

    expect($invoice)->not->toBeNull();
    expect($invoice?->currency)->toBe('EUR');

    expect(LineItem::query()
        ->where('documentable_type', Invoice::class)
        ->where('documentable_id', $invoice?->id)
        ->count())->toBe(1);
});
