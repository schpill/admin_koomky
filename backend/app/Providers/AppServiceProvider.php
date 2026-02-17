<?php

namespace App\Providers;

use App\Listeners\LogAuthEvent;
use App\Models\Client;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
use App\Observers\ClientObserver;
use App\Observers\ContactObserver;
use App\Observers\CreditNoteObserver;
use App\Observers\InvoiceObserver;
use App\Observers\ProjectObserver;
use App\Observers\QuoteObserver;
use App\Observers\TaskObserver;
use App\Services\ExchangeRates\ApiExchangeRateService;
use App\Services\ExchangeRates\EcbExchangeRatesDriver;
use App\Services\ExchangeRates\ExchangeRateDriver;
use App\Services\ExchangeRates\ExchangeRateService;
use App\Services\ExchangeRates\OpenExchangeRatesDriver;
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
        $this->app->bind(ExchangeRateDriver::class, function () {
            $provider = (string) config('services.exchange_rates.provider', 'open_exchange_rates');

            return $provider === 'ecb'
                ? new EcbExchangeRatesDriver()
                : new OpenExchangeRatesDriver();
        });

        $this->app->bind(ExchangeRateService::class, ApiExchangeRateService::class);
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
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Quote::observe(QuoteObserver::class);
        CreditNote::observe(CreditNoteObserver::class);
    }
}
