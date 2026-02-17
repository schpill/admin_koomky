<?php

use App\Models\Client;
use App\Models\RecurringInvoiceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('recurring invoice profile factory creates valid model', function () {
    $profile = RecurringInvoiceProfile::factory()->create();

    expect($profile->id)->toBeString();
    expect($profile->frequency)->toBeString();
    expect($profile->line_items)->toBeArray();
});

test('recurring invoice profile relationships are configured', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $profile = RecurringInvoiceProfile::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $invoice = $profile->invoices()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'number' => 'FAC-2026-1001',
        'status' => 'draft',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'subtotal' => 100,
        'tax_amount' => 20,
        'discount_amount' => 0,
        'total' => 120,
        'currency' => 'EUR',
    ]);

    expect($profile->user)->toBeInstanceOf(User::class);
    expect($profile->client)->toBeInstanceOf(Client::class);
    expect($profile->invoices->first()?->id)->toBe($invoice->id);
});

test('recurring invoice profile scopes active and due work', function () {
    $activeDue = RecurringInvoiceProfile::factory()->create([
        'status' => 'active',
        'next_due_date' => now()->toDateString(),
    ]);

    RecurringInvoiceProfile::factory()->create([
        'status' => 'paused',
        'next_due_date' => now()->toDateString(),
    ]);

    RecurringInvoiceProfile::factory()->create([
        'status' => 'active',
        'next_due_date' => now()->addDay()->toDateString(),
    ]);

    $active = RecurringInvoiceProfile::query()->active()->pluck('id')->all();
    $due = RecurringInvoiceProfile::query()->due()->pluck('id')->all();

    expect($active)->toContain($activeDue->id);
    expect($due)->toContain($activeDue->id);
});

