<?php

use App\Models\EmailWarmupPlan;
use App\Models\User;
use App\Services\WarmupGuardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('canSend returns false when quota reached and reset restores capacity', function () {
    $user = User::factory()->create([
        'warmup_sent_today' => 2,
        'warmup_last_reset_at' => now()->toDateString(),
    ]);

    EmailWarmupPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'current_daily_limit' => 2,
    ]);

    $service = app(WarmupGuardService::class);

    expect($service->canSend($user->fresh()))->toBeFalse();

    $user->forceFill([
        'warmup_last_reset_at' => now()->subDay()->toDateString(),
    ])->save();

    $service->resetDailyCountIfNeeded($user->fresh());

    expect($user->fresh()->warmup_sent_today)->toBe(0)
        ->and($service->canSend($user->fresh()))->toBeTrue();
});

test('incrementSentCount increases warmup counter', function () {
    $user = User::factory()->create([
        'warmup_sent_today' => 0,
        'warmup_last_reset_at' => now()->toDateString(),
    ]);

    $service = app(WarmupGuardService::class);
    $service->incrementSentCount($user);

    expect($user->fresh()->warmup_sent_today)->toBe(1);
});
