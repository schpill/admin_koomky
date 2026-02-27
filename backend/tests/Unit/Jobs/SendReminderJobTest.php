<?php

use App\Jobs\SendReminderJob;
use App\Mail\ReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderDelivery;
use App\Models\ReminderSequence;
use App\Models\User;
use App\Services\ReminderDispatchService;
use App\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('handle sends mail creates sent delivery and advances step', function () {
    Mail::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'client@example.test',
    ]);

    $sequence = ReminderSequence::factory()->withSteps(2)->create(['user_id' => $user->id]);
    $firstStep = $sequence->steps()->orderBy('step_number')->firstOrFail();

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $schedule = InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'next_reminder_step_id' => $firstStep->id,
    ]);

    $service = app(ReminderDispatchService::class);
    $webhook = \Mockery::mock(WebhookDispatchService::class);
    $webhook->shouldReceive('dispatch')->once();

    (new SendReminderJob($schedule->id))->handle($service, $webhook);

    Mail::assertQueued(ReminderMail::class);

    $delivery = ReminderDelivery::query()
        ->where('invoice_id', $invoice->id)
        ->where('reminder_step_id', $firstStep->id)
        ->first();

    expect($delivery?->status)->toBe('sent');
    $schedule->refresh();
    expect($schedule->next_reminder_step_id)->not()->toBe($firstStep->id);
});

test('failed creates failed delivery', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps(1)->create(['user_id' => $user->id]);
    $step = $sequence->steps()->firstOrFail();

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);

    $schedule = InvoiceReminderSchedule::query()->create([
        'invoice_id' => $invoice->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'next_reminder_step_id' => $step->id,
    ]);

    $job = new SendReminderJob($schedule->id);
    $job->failed(new RuntimeException('smtp error'));

    $delivery = ReminderDelivery::query()
        ->where('invoice_id', $invoice->id)
        ->where('reminder_step_id', $step->id)
        ->first();

    expect($delivery?->status)->toBe('failed');
    expect($delivery?->error_message)->toBe('smtp error');
});
