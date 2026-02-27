<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('invoice observer creates schedule on overdue when default active sequence exists', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $sequence = ReminderSequence::factory()->withSteps()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'due_date' => now()->subDays(5),
    ]);

    $invoice->update(['status' => 'overdue']);

    $schedule = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->first();

    expect($schedule)->not()->toBeNull();
    expect($schedule?->sequence_id)->toBe($sequence->id);
    expect($schedule?->next_reminder_step_id)->toBe($sequence->steps()->orderBy('step_number')->first()?->id);
});

test('invoice observer does not create duplicate schedule', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $sequence = ReminderSequence::factory()->withSteps()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => $invoice->due_date,
        'next_reminder_step_id' => $sequence->steps()->first()?->id,
    ]);

    $invoice->update(['status' => 'overdue']);

    expect(InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->count())->toBe(1);
});

test('invoice observer completes schedule on paid and cancelled', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);
    $step = $sequence->steps()->first();

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => $invoice->due_date,
        'next_reminder_step_id' => $step?->id,
    ]);

    $invoice->update(['status' => 'paid']);
    $scheduleAfterPaid = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->first();
    expect($scheduleAfterPaid?->completed_at)->not()->toBeNull();

    $invoice2 = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice2->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => $invoice2->due_date,
        'next_reminder_step_id' => $step?->id,
    ]);

    $invoice2->update(['status' => 'cancelled']);
    $scheduleAfterCancelled = InvoiceReminderSchedule::query()->where('invoice_id', $invoice2->id)->first();
    expect($scheduleAfterCancelled?->completed_at)->not()->toBeNull();
});

test('invoice observer does nothing when no default active sequence', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    ReminderSequence::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'is_active' => false,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $invoice->update(['status' => 'overdue']);

    expect(InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->exists())->toBeFalse();
});
