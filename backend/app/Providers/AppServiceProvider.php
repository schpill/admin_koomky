<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
}
