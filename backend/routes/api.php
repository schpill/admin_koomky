<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TagController;
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

    // Auth Routes (Public or throttled)
    Route::prefix('auth')->middleware('throttle:api_auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', DashboardController::class);

        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // Settings
                    Route::prefix('settings')->group(function () {
                        Route::get('/profile', [UserSettingsController::class, 'profile']);
                        Route::put('/profile', [UserSettingsController::class, 'updateProfile']);
                        Route::put('/business', [UserSettingsController::class, 'updateBusiness']);
                        
                        // 2FA
                        Route::post('/2fa/enable', [UserSettingsController::class, 'enable2fa']);
                        Route::post('/2fa/confirm', [UserSettingsController::class, 'confirm2fa']);
                        Route::post('/2fa/disable', [UserSettingsController::class, 'disable2fa']);
                    });
        
                                                        // Clients
        
                                                        Route::get('clients/export/csv', [ClientController::class, 'exportCsv']);
        
                                                        Route::post('clients/import/csv', [ClientController::class, 'importCsv']);
        
                                                        Route::apiResource('clients', ClientController::class);
        
                                            
        
                                            Route::apiResource('clients.contacts', ContactController::class)->only(['index', 'store', 'update', 'destroy']);
        
                                            Route::post('clients/{client}/tags', [TagController::class, 'attachToClient']);
        
                                
        
                                                        // Tags
        
                                
        
                                                        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'destroy']);
        
                                
        
                                            
        
                                
        
                                                        // Search
        
                                
        
                                                        Route::get('search', SearchController::class);
        
                                
        
                                                    });
        
                                
        
                                            
        
                                
                    });
