<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Blade::directive('currency', function ($expression) {
            return "<?php echo number_format($expression, 2, ',', ' ') . ' €'; ?>";
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
    }
}
