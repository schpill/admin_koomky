<?php

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mark overdue command updates only eligible invoices', function () {
    $sentPastDue = Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $viewedPastDue = Invoice::factory()->create([
        'status' => 'viewed',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $paidPastDue = Invoice::factory()->create([
        'status' => 'paid',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $sentNotDue = Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->addDay()->toDateString(),
    ]);

    $this->artisan('invoices:mark-overdue')
        ->assertExitCode(0);

    expect(Invoice::findOrFail($sentPastDue->id)->status)->toBe('overdue');
    expect(Invoice::findOrFail($viewedPastDue->id)->status)->toBe('overdue');
    expect(Invoice::findOrFail($paidPastDue->id)->status)->toBe('paid');
    expect(Invoice::findOrFail($sentNotDue->id)->status)->toBe('sent');
});
