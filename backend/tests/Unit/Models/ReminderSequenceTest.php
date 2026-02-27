<?php

use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderSequence;
use App\Models\ReminderStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('reminder sequence relationships and casts are configured', function () {
    $user = User::factory()->create();

    $sequence = ReminderSequence::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
        'is_default' => false,
    ]);

    $step = ReminderStep::factory()->create([
        'sequence_id' => $sequence->id,
        'step_number' => 1,
    ]);

    $schedule = InvoiceReminderSchedule::query()->create([
        'invoice_id' => \App\Models\Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => \App\Models\Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'sequence_id' => $sequence->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'next_reminder_step_id' => $step->id,
    ]);

    expect($sequence->user->id)->toBe($user->id);
    expect($sequence->steps->first()?->id)->toBe($step->id);
    expect($sequence->invoiceSchedules->first()?->id)->toBe($schedule->id);
    expect($sequence->is_active)->toBeTrue();
    expect($sequence->is_default)->toBeFalse();
});

test('reminder sequence scopes active and default work', function () {
    $activeDefault = ReminderSequence::factory()->create([
        'is_active' => true,
        'is_default' => true,
    ]);

    ReminderSequence::factory()->create([
        'is_active' => false,
        'is_default' => false,
    ]);

    $activeIds = ReminderSequence::query()->active()->pluck('id')->all();
    $defaultIds = ReminderSequence::query()->default()->pluck('id')->all();

    expect($activeIds)->toContain($activeDefault->id);
    expect($defaultIds)->toContain($activeDefault->id);
});
