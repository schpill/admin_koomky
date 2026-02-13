<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TwoFactorAuthController;
use App\Http\Controllers\Api\V1\UserSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health Check
    Route::get('/health', HealthController::class)->name('health');

    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');

    // Password reset (public with token)
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // 2FA confirmation during login
    Route::post('/auth/2fa/confirm', [TwoFactorAuthController::class, 'confirm'])->name('auth.2fa.confirm');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // 2FA
        Route::prefix('auth/2fa')->group(function () {
            Route::post('/enable', [TwoFactorAuthController::class, 'enable'])->name('auth.2fa.enable');
            Route::post('/verify', [TwoFactorAuthController::class, 'verify'])->name('auth.2fa.verify');
            Route::delete('/disable', [TwoFactorAuthController::class, 'disable'])->name('auth.2fa.disable');
            Route::post('/recovery-codes', [TwoFactorAuthController::class, 'recoveryCodes'])->name('auth.2fa.recovery-codes');
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [UserSettingsController::class, 'index'])->name('settings.index');
            Route::put('/', [UserSettingsController::class, 'update'])->name('settings.update');
            Route::post('/avatar', [UserSettingsController::class, 'uploadAvatar'])->name('settings.avatar');
        });

        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Api\V1\DashboardController::class, 'index'])->name('dashboard');

        // Import/Export
        Route::prefix('import')->group(function () {
            Route::get('/template', [App\Http\Controllers\Api\V1\ImportController::class, 'template'])->name('import.template');
            Route::post('/clients', [App\Http\Controllers\Api\V1\ImportController::class, 'import'])->name('import.clients');
            Route::get('/export', [App\Http\Controllers\Api\V1\ImportController::class, 'export'])->name('export.clients');
        });

        // Tags
        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        // Clients
        Route::apiResource('clients', ClientController::class);

        // Client contacts
        Route::prefix('clients/{client}')->group(function () {
            Route::post('/contacts', [ContactController::class, 'store'])->name('clients.contacts.store');

            Route::prefix('contacts/{contact}')->group(function () {
                Route::get('/', [ContactController::class, 'show'])->name('clients.contacts.show');
                Route::put('/', [ContactController::class, 'update'])->name('clients.contacts.update');
                Route::delete('/', [ContactController::class, 'destroy'])->name('clients.contacts.destroy');
            });
        });

        // Client actions
        Route::prefix('clients/{client}')->group(function () {
            Route::post('/archive', [ClientController::class, 'archive'])->name('clients.archive');
            Route::post('/restore', [ClientController::class, 'restore'])->name('clients.restore');
        });
    });

});
