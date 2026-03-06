<?php

namespace App\Providers;

use App\Listeners\LogAuthEvent;
use App\Models\Client;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\Document;
use App\Models\DripSequence;
use App\Models\Expense;
use App\Models\ImportSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Quote;
use App\Models\ReminderSequence;
use App\Models\SuppressedEmail;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Workflow;
use App\Observers\CampaignRecipientObserver;
use App\Observers\ClientObserver;
use App\Observers\ContactObserver;
use App\Observers\CreditNoteObserver;
use App\Observers\DocumentObserver;
use App\Observers\ExpenseObserver;
use App\Observers\InvoiceObserver;
use App\Observers\LeadObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProjectObserver;
use App\Observers\QuoteObserver;
use App\Observers\TaskObserver;
use App\Observers\TicketMessageObserver;
use App\Observers\TicketObserver;
use App\Policies\DripSequencePolicy;
use App\Policies\ImportSessionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductSalePolicy;
use App\Policies\ProjectTemplatePolicy;
use App\Policies\ReminderSequencePolicy;
use App\Policies\SuppressedEmailPolicy;
use App\Policies\WorkflowPolicy;
use App\Services\ExchangeRates\ApiExchangeRateService;
use App\Services\ExchangeRates\EcbExchangeRatesDriver;
use App\Services\ExchangeRates\ExchangeRateDriver;
use App\Services\ExchangeRates\ExchangeRateService;
use App\Services\ExchangeRates\OpenExchangeRatesDriver;
use Dedoc\Scramble\Scramble;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
                ? new EcbExchangeRatesDriver
                : new OpenExchangeRatesDriver;
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
        \App\Models\CampaignRecipient::observe(CampaignRecipientObserver::class);
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Quote::observe(QuoteObserver::class);
        CreditNote::observe(CreditNoteObserver::class);
        Expense::observe(ExpenseObserver::class);
        Payment::observe(PaymentObserver::class);
        Lead::observe(LeadObserver::class);
        Document::observe(DocumentObserver::class);
        Ticket::observe(TicketObserver::class);
        TicketMessage::observe(TicketMessageObserver::class);

        // Configure Scramble OpenAPI documentation routes
        Scramble::configure()
            ->expose(
                ui: 'api/docs',
                document: 'api/docs.json'
            );

        // Register policies
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductSale::class, ProductSalePolicy::class);
        Gate::policy(ReminderSequence::class, ReminderSequencePolicy::class);
        Gate::policy(ProjectTemplate::class, ProjectTemplatePolicy::class);
        Gate::policy(ImportSession::class, ImportSessionPolicy::class);
        Gate::policy(SuppressedEmail::class, SuppressedEmailPolicy::class);
        Gate::policy(DripSequence::class, DripSequencePolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
    }
}
