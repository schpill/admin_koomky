<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->product = Product::factory()->create(['user_id' => $this->user->id]);
    Queue::fake();
});

test('it creates product sale when invoice is paid', function () {
    $invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
    ]);

    $invoice->lineItems()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 500.00,
        'vat_rate' => 20,
        'total' => 1000.00,
        'description' => 'Produit formation',
        'sort_order' => 0,
    ]);

    $invoice->update(['status' => 'paid']);

    $this->assertDatabaseHas('product_sales', [
        'product_id' => $this->product->id,
        'invoice_id' => $invoice->id,
        'client_id' => $this->client->id,
        'quantity' => 2,
        'unit_price' => 500.00,
        'total_price' => 1000.00,
        'status' => 'confirmed',
    ]);
});

test('it prevents duplicate product sales for same invoice line item', function () {
    $invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
    ]);

    $invoice->lineItems()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 100.00,
        'total' => 100.00,
        'description' => 'Produit service',
        'sort_order' => 0,
    ]);

    // Trigger observer
    $invoice->update(['status' => 'paid']);

    // Trigger again - should not create duplicate
    $invoice->update(['status' => 'paid']);

    $this->assertDatabaseCount('product_sales', 1);
});

test('it creates product sale when quote is accepted', function () {
    $quote = Quote::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
    ]);

    $quote->lineItems()->create([
        'documentable_type' => Quote::class,
        'documentable_id' => $quote->id,
        'product_id' => $this->product->id,
        'quantity' => 3,
        'unit_price' => 300.00,
        'vat_rate' => 20,
        'total' => 900.00,
        'description' => 'Produit quote',
        'sort_order' => 0,
    ]);

    $quote->update(['status' => 'accepted']);

    $this->assertDatabaseHas('product_sales', [
        'product_id' => $this->product->id,
        'quote_id' => $quote->id,
        'client_id' => $this->client->id,
        'quantity' => 3,
        'unit_price' => 300.00,
        'total_price' => 900.00,
        'status' => 'pending',
    ]);
});

test('it ignores line items without product_id when creating product sales', function () {
    $invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
    ]);

    $invoice->lineItems()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'product_id' => null,
        'description' => 'Custom service',
        'quantity' => 1,
        'unit_price' => 500.00,
        'total' => 500.00,
        'sort_order' => 0,
    ]);

    $invoice->update(['status' => 'paid']);

    $this->assertDatabaseCount('product_sales', 0);
});

test('it dispatches product sold webhook when invoice is paid', function () {
    $fakeWebhookService = new class
    {
        /** @var array<int, array{event: string, data: array<string, mixed>, user_id: string}> */
        public array $calls = [];

        /**
         * @param  array<string, mixed>  $data
         */
        public function dispatch(string $event, array $data, string $userId): void
        {
            $this->calls[] = [
                'event' => $event,
                'data' => $data,
                'user_id' => $userId,
            ];
        }
    };

    app()->instance(WebhookDispatchService::class, $fakeWebhookService);

    $invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
    ]);

    $invoice->lineItems()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 200.00,
        'total' => 200.00,
        'description' => 'Produit webhook',
        'sort_order' => 0,
    ]);

    $invoice->update(['status' => 'paid']);

    $productSoldCall = collect($fakeWebhookService->calls)->firstWhere('event', 'product.sold');

    expect($productSoldCall)->not->toBeNull();
    expect($productSoldCall['user_id'])->toBe($this->user->id);
    expect($productSoldCall['data'])->toMatchArray([
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'client_id' => $this->client->id,
        'currency_code' => $invoice->currency,
    ]);
});
