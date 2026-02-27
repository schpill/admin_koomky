<?php

use App\Jobs\SendReminderJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderSequence;
use App\Models\User;
use App\Services\ReminderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('dispatchDue dispatches jobs for due active schedules only', function () {
    Bus::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);
    $first = $sequence->steps()->orderBy('step_number')->firstOrFail();

    $dueInvoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    $pausedInvoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    $futureInvoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $dueInvoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'is_paused' => false,
        'next_reminder_step_id' => $first->id,
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $pausedInvoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'is_paused' => true,
        'next_reminder_step_id' => $first->id,
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $futureInvoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'is_paused' => false,
        'next_reminder_step_id' => $first->id,
    ]);

    $count = app(ReminderDispatchService::class)->dispatchDue();

    expect($count)->toBe(1);
    Bus::assertDispatched(SendReminderJob::class, 1);
});

test('completeSchedule sets completed_at', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'next_reminder_step_id' => $sequence->steps()->first()?->id,
    ]);

    app(ReminderDispatchService::class)->completeSchedule($invoice);

    $schedule = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->first();
    expect($schedule?->completed_at)->not()->toBeNull();
    expect($schedule?->next_reminder_step_id)->toBeNull();
});

test('advanceStep moves to next step then completes on last', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps(2)->create(['user_id' => $user->id]);
    $steps = $sequence->steps()->orderBy('step_number')->get();

    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    $schedule = InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'next_reminder_step_id' => $steps[0]->id,
    ]);

    $service = app(ReminderDispatchService::class);
    $service->advanceStep($schedule);

    $schedule->refresh();
    expect($schedule->next_reminder_step_id)->toBe($steps[1]->id);
    expect($schedule->completed_at)->toBeNull();

    $service->advanceStep($schedule);
    $schedule->refresh();
    expect($schedule->next_reminder_step_id)->toBeNull();
    expect($schedule->completed_at)->not()->toBeNull();
});
