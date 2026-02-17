<?php

use App\Models\RecurringInvoiceProfile;
use App\Services\RecurringInvoiceGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('monthly day 31 rolls to last day of shorter month', function () {
    Carbon::setTestNow('2026-01-31 00:00:00');

    $profile = RecurringInvoiceProfile::factory()->create([
        'frequency' => 'monthly',
        'next_due_date' => '2026-01-31',
        'day_of_month' => 31,
        'status' => 'active',
    ]);

    app(RecurringInvoiceGeneratorService::class)->generate($profile);

    expect($profile->fresh()->next_due_date->toDateString())->toBe('2026-02-28');

    Carbon::setTestNow();
});

test('leap year profile advances safely when target day does not exist', function () {
    Carbon::setTestNow('2028-02-29 00:00:00');

    $profile = RecurringInvoiceProfile::factory()->create([
        'frequency' => 'annual',
        'next_due_date' => '2028-02-29',
        'day_of_month' => 29,
        'status' => 'active',
    ]);

    app(RecurringInvoiceGeneratorService::class)->generate($profile);

    expect($profile->fresh()->next_due_date->toDateString())->toBe('2029-02-28');

    Carbon::setTestNow();
});

test('profile without end date keeps generating indefinitely', function () {
    $profile = RecurringInvoiceProfile::factory()->create([
        'frequency' => 'weekly',
        'next_due_date' => now()->toDateString(),
        'end_date' => null,
        'max_occurrences' => null,
        'status' => 'active',
    ]);

    $service = app(RecurringInvoiceGeneratorService::class);

    $service->generate($profile);
    $service->generate($profile->fresh());
    $service->generate($profile->fresh());

    $profile->refresh();

    expect($profile->status)->toBe('active');
    expect($profile->occurrences_generated)->toBe(3);
});

