<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('monitor failed jobs command logs warning when threshold is exceeded', function () {
    config()->set('performance.failed_jobs_alert_threshold', 2);

    DB::table('failed_jobs')->insert([
        [
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException',
            'failed_at' => now()->subMinutes(20),
        ],
        [
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException',
            'failed_at' => now()->subMinutes(10),
        ],
        [
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException',
            'failed_at' => now()->subMinutes(5),
        ],
    ]);

    Log::spy();

    $this->artisan('queue:monitor-failures')
        ->expectsOutputToContain('Failed jobs in the last hour: 3')
        ->assertExitCode(1);

    Log::shouldHaveReceived('warning')->with(
        'failed_jobs_threshold_exceeded',
        Mockery::on(fn (array $context): bool => $context['count'] === 3 && $context['threshold'] === 2)
    )->once();
});

test('monitor failed jobs command logs info when count is below threshold', function () {
    config()->set('performance.failed_jobs_alert_threshold', 10);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'RuntimeException',
        'failed_at' => now()->subMinutes(15),
    ]);

    Log::spy();

    $this->artisan('queue:monitor-failures')
        ->expectsOutputToContain('Failed jobs in the last hour: 1')
        ->assertExitCode(0);

    Log::shouldHaveReceived('info')->with(
        'failed_jobs_within_threshold',
        Mockery::on(fn (array $context): bool => $context['count'] === 1 && $context['threshold'] === 10)
    )->once();
});
