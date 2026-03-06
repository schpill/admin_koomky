<?php

use App\Jobs\ResetWarmupCountersJob;
use App\Models\EmailWarmupPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('reset warmup counters job resets counters advances plan and completes at max volume', function () {
    $user = User::factory()->create([
        'warmup_sent_today' => 7,
        'warmup_last_reset_at' => now()->subDay()->toDateString(),
    ]);

    $plan = EmailWarmupPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'daily_volume_start' => 100,
        'daily_volume_max' => 120,
        'increment_percent' => 30,
        'current_day' => 0,
        'current_daily_limit' => 100,
    ]);

    app(ResetWarmupCountersJob::class)->handle(app(\App\Services\WarmupGuardService::class));

    $plan->refresh();
    $user->refresh();

    expect($user->warmup_sent_today)->toBe(0)
        ->and($plan->current_day)->toBe(1)
        ->and($plan->current_daily_limit)->toBe(120)
        ->and($plan->status)->toBe('completed');
});
