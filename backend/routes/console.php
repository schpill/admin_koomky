<?php

use App\Jobs\AdvanceDripEnrollmentsJob;
use App\Jobs\RecalculateExpiredScoresJob;
use App\Jobs\ResetWarmupCountersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->dailyAt('01:00');
Schedule::command('invoices:generate-recurring')->dailyAt('01:05');
Schedule::command('quotes:mark-expired')->dailyAt('01:10');
Schedule::command('exchange-rates:fetch')->dailyAt('02:00');
Schedule::command('calendar:sync')->everyFifteenMinutes();
Schedule::command('campaigns:dispatch-scheduled')->everyMinute();
Schedule::job(new AdvanceDripEnrollmentsJob)->everyFiveMinutes();
Schedule::command('queue:monitor-failures')->everyFiveMinutes();
Schedule::command('reminders:dispatch')->dailyAt('08:00');
Schedule::job(new RecalculateExpiredScoresJob)->dailyAt('03:00');
Schedule::job(new ResetWarmupCountersJob)->dailyAt('00:01');
Schedule::command('import-sessions:prune')->weekly();
Schedule::command('drip-enrollments:prune')->weekly();
