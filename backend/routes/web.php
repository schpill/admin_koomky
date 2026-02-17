<?php

use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/unsubscribe/{contact}', UnsubscribeController::class)
    ->name('unsubscribe');
