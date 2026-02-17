<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->dailyAt('01:00');
Schedule::command('quotes:mark-expired')->dailyAt('01:10');
Schedule::command('campaigns:dispatch-scheduled')->everyMinute();
Schedule::command('queue:monitor-failures')->everyFiveMinutes();
