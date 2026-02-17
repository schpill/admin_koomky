<?php

use App\Http\Controllers\Api\V1\CampaignWebhookController;
use App\Http\Controllers\Api\V1\EmailTrackingController;
use App\Http\Controllers\SmsWebhookController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/unsubscribe/{contact}', UnsubscribeController::class)
    ->name('unsubscribe');

Route::get('/t/open/{token}', [EmailTrackingController::class, 'open']);
Route::get('/t/click/{token}', [EmailTrackingController::class, 'click']);
Route::post('/webhooks/email', CampaignWebhookController::class);
Route::post('/webhooks/sms', SmsWebhookController::class);
