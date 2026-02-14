<?php

namespace App\Providers;

use App\Listeners\LogAuthEvent;
use App\Models\Client;
use App\Models\Contact;
use App\Observers\ClientObserver;
use App\Observers\ContactObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api_auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Event::listen([
            Login::class,
            Logout::class,
            Registered::class,
        ], LogAuthEvent::class);

        Client::observe(ClientObserver::class);
        Contact::observe(ContactObserver::class);
    }
}
