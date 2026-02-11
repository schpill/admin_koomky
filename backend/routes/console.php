<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $quote = Inspiring::quote();
    $this->comment(sprintf("Inspiring quote: %s", $quote));
})->purpose('Display an inspiring quote')->hourly();
