<?php

use App\Models\Client;
use App\Models\RecurringInvoiceProfile;
use App\Models\User;
use App\Services\RecurringInvoiceGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generator creates invoice from recurring profile and updates counters', function () {
    Carbon::setTestNow('2026-01-10 09:00:00');

    $user = User::factory()->create(['payment_terms_days' => 45]);
    $client = Client::factory()->create(['user_id' => $user->id]);

    $profile = RecurringInvoiceProfile::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'frequency' => 'monthly',
        'next_due_date' => '2026-01-10',
        'payment_terms_days' => 30,
        'line_items' => [
            [
                'description' => 'Retainer',
                'quantity' => 1,
                'unit_price' => 1000,
                'vat_rate' => 20,
            ],
        ],
        'discount_percent' => 10,
        'occurrences_generated' => 0,
    ]);

    $service = app(RecurringInvoiceGeneratorService::class);

    $invoice = $service->generate($profile);

    $profile->refresh();
    $invoice->load('lineItems');

    expect($invoice->client_id)->toBe($client->id);
    expect($invoice->recurring_invoice_profile_id)->toBe($profile->id);
    expect($invoice->due_date->toDateString())->toBe('2026-02-09');
    expect($invoice->lineItems)->toHaveCount(1);
    expect($profile->occurrences_generated)->toBe(1);
    expect($profile->next_due_date->toDateString())->toBe('2026-02-10');

    Carbon::setTestNow();
});

test('generator marks profile completed when max occurrences reached', function () {
    $profile = RecurringInvoiceProfile::factory()->create([
        'max_occurrences' => 1,
        'occurrences_generated' => 0,
        'status' => 'active',
        'next_due_date' => now()->toDateString(),
    ]);

    app(RecurringInvoiceGeneratorService::class)->generate($profile);

    $profile->refresh();

    expect($profile->status)->toBe('completed');
    expect($profile->occurrences_generated)->toBe(1);
});

test('generator marks profile completed when end date passed', function () {
    Carbon::setTestNow('2026-04-15 00:00:00');

    $profile = RecurringInvoiceProfile::factory()->create([
        'frequency' => 'monthly',
        'next_due_date' => '2026-04-15',
        'end_date' => '2026-04-16',
        'status' => 'active',
    ]);

    app(RecurringInvoiceGeneratorService::class)->generate($profile);

    $profile->refresh();

    expect($profile->status)->toBe('completed');

    Carbon::setTestNow();
});

test('generator computes next due date for all frequencies', function (string $frequency, string $expectedNextDate) {
    Carbon::setTestNow('2026-01-15 00:00:00');

    $profile = RecurringInvoiceProfile::factory()->create([
        'frequency' => $frequency,
        'next_due_date' => '2026-01-15',
        'status' => 'active',
        'day_of_month' => 15,
    ]);

    app(RecurringInvoiceGeneratorService::class)->generate($profile);

    expect($profile->fresh()->next_due_date->toDateString())->toBe($expectedNextDate);

    Carbon::setTestNow();
})->with([
    ['weekly', '2026-01-22'],
    ['biweekly', '2026-01-29'],
    ['monthly', '2026-02-15'],
    ['quarterly', '2026-04-15'],
    ['semiannual', '2026-07-15'],
    ['annual', '2027-01-15'],
]);

