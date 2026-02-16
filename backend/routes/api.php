<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoicingSettingsController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectInvoiceController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\UserSettingsController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('api_auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    // Auth Routes
    Route::prefix('auth')->middleware('throttle:api_auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected Routes
    Route::middleware(['auth:sanctum', 'two-factor'])->group(function () {
        Route::get('dashboard', DashboardController::class);

        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/2fa/verify', [AuthController::class, 'verify2fa']);
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/profile', [UserSettingsController::class, 'profile']);
            Route::put('/profile', [UserSettingsController::class, 'updateProfile']);
            Route::put('/business', [UserSettingsController::class, 'updateBusiness']);
            Route::get('/invoicing', [InvoicingSettingsController::class, 'show']);
            Route::put('/invoicing', [InvoicingSettingsController::class, 'update']);

            // 2FA Management
            Route::post('/2fa/enable', [UserSettingsController::class, 'enable2fa']);
            Route::post('/2fa/confirm', [UserSettingsController::class, 'confirm2fa']);
            Route::post('/2fa/disable', [UserSettingsController::class, 'disable2fa']);
        });

        // Clients
        Route::get('clients/export/csv', [ClientController::class, 'exportCsv']);
        Route::post('clients/import/csv', [ClientController::class, 'importCsv']);
        Route::post('clients/{client}/restore', [ClientController::class, 'restore']);
        Route::apiResource('clients', ClientController::class);

        // Projects
        Route::apiResource('projects', ProjectController::class);
        Route::prefix('projects/{project}')->group(function () {
            Route::post('generate-invoice', [ProjectInvoiceController::class, 'generate']);

            // Tasks
            Route::get('tasks', [TaskController::class, 'index']);
            Route::post('tasks', [TaskController::class, 'store']);
            Route::post('tasks/reorder', [TaskController::class, 'reorder']);
            Route::get('tasks/{task}', [TaskController::class, 'show']);
            Route::put('tasks/{task}', [TaskController::class, 'update']);
            Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
            Route::post('tasks/{task}/dependencies', [TaskController::class, 'addDependency']);

            // Task Attachments
            Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment']);
            Route::get('tasks/{task}/attachments/{attachment}', [TaskController::class, 'downloadAttachment']);
            Route::delete('tasks/{task}/attachments/{attachment}', [TaskController::class, 'deleteAttachment']);

            // Task Time Entries
            Route::post('tasks/{task}/time-entries', [TimeEntryController::class, 'store']);
            Route::put('tasks/{task}/time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
            Route::delete('tasks/{task}/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
        });

        // Client Contacts
        Route::apiResource('clients.contacts', ContactController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store']);

        // Tags
        Route::post('clients/{client}/tags', [TagController::class, 'attachToClient']);
        Route::delete('clients/{client}/tags/{tag}', [TagController::class, 'detachFromClient']);
        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'destroy']);

        // Search & Activities
        Route::get('search', SearchController::class);
        Route::get('activities', [ActivityController::class, 'index']);
    });
});
