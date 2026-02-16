<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSimpleInvoice(User $user, Client $client): void
{
    $response = test()->actingAs($user, 'sanctum')->postJson('/api/v1/invoices', [
        'client_id' => $client->id,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(15)->toDateString(),
        'line_items' => [
            [
                'description' => 'Work',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 20,
            ],
        ],
    ]);

    $response->assertStatus(201);
}

test('invoice numbering is sequential', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    createSimpleInvoice($user, $client);
    createSimpleInvoice($user, $client);

    $numbers = Invoice::query()->orderBy('number')->pluck('number')->values()->all();

    expect($numbers[0])->toBe('FAC-'.date('Y').'-0001');
    expect($numbers[1])->toBe('FAC-'.date('Y').'-0002');
});

test('invoice numbering resets on year rollover', function () {
    Carbon::setTestNow(Carbon::create(2026, 12, 31, 10, 0, 0));

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    createSimpleInvoice($user, $client);

    Carbon::setTestNow(Carbon::create(2027, 1, 1, 10, 0, 0));

    createSimpleInvoice($user, $client);

    expect(Invoice::query()->where('number', 'FAC-2026-0001')->exists())->toBeTrue();
    expect(Invoice::query()->where('number', 'FAC-2027-0001')->exists())->toBeTrue();

    Carbon::setTestNow();
});

test('numbering has no reuse after deletion', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    createSimpleInvoice($user, $client);
    createSimpleInvoice($user, $client);

    $second = Invoice::query()->where('number', 'FAC-'.date('Y').'-0002')->firstOrFail();
    $second->delete();

    createSimpleInvoice($user, $client);

    expect(Invoice::query()->where('number', 'FAC-'.date('Y').'-0003')->exists())->toBeTrue();
});
