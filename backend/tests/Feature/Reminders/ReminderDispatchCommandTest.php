<?php

use App\Jobs\SendReminderJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('reminders dispatch command dispatches due jobs and outputs count', function () {
    Bus::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);
    $step = $sequence->steps()->orderBy('step_number')->firstOrFail();

    $dueInvoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    $futureInvoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $dueInvoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'next_reminder_step_id' => $step->id,
    ]);

    InvoiceReminderSchedule::query()->create([
        'invoice_id' => $futureInvoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'next_reminder_step_id' => $step->id,
    ]);

    $this->artisan('reminders:dispatch')
        ->expectsOutput('1 relances dispatchées.')
        ->assertSuccessful();

    Bus::assertDispatched(SendReminderJob::class, 1);
});
