<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

final class Google2FAServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Google2FA::class, function ($app) {
            return new Google2FA;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('google2fa.enabled')) {
            $this->loadViewsFrom(resource_path('views/vendor/google2fa'), 'google2fa');
        }
    }
}
