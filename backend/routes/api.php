<?php

use App\Http\Controllers\Api\V1\AccountDeletionController;
use App\Http\Controllers\Api\V1\Accounting\AccountingExportController;
use App\Http\Controllers\Api\V1\Accounting\AccountingSettingsController;
use App\Http\Controllers\Api\V1\Accounting\FecExportController;
use App\Http\Controllers\Api\V1\Accounting\FiscalYearSummaryController;
use App\Http\Controllers\Api\V1\Accounting\VatDeclarationController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarConnectionController;
use App\Http\Controllers\Api\V1\CalendarEventController;
use App\Http\Controllers\Api\V1\CampaignAnalyticsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CampaignTemplateController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CreditNoteController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DataExportController;
use App\Http\Controllers\Api\V1\DataImportController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ExpenseReportController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoicingSettingsController;
use App\Http\Controllers\Api\V1\LeadActivityController;
use App\Http\Controllers\Api\V1\LeadAnalyticsController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\LeadConversionController;
use App\Http\Controllers\Api\V1\LeadPipelineController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PortalAccessTokenController;
use App\Http\Controllers\Api\V1\PortalAuthController;
use App\Http\Controllers\Api\V1\PortalDashboardController;
use App\Http\Controllers\Api\V1\PortalInvoiceController;
use App\Http\Controllers\Api\V1\PortalPaymentController;
use App\Http\Controllers\Api\V1\PortalQuoteController;
use App\Http\Controllers\Api\V1\PortalSettingsController;
use App\Http\Controllers\Api\V1\ProfitLossController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectExpenseController;
use App\Http\Controllers\Api\V1\ProjectInvoiceController;
use App\Http\Controllers\Api\V1\ProjectProfitabilityController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\RecurringInvoiceProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\Settings\PersonalAccessTokenController as SettingsPersonalAccessTokenController;
use App\Http\Controllers\Api\V1\Settings\WebhookDeliveryController;
use App\Http\Controllers\Api\V1\Settings\WebhookEndpointController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
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

RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    // Portal Routes
    Route::prefix('portal')->group(function () {
        Route::post('/auth/request', [PortalAuthController::class, 'requestMagicLink']);
        Route::get('/auth/verify/{token}', [PortalAuthController::class, 'verify']);

        Route::middleware('portal-auth')->group(function () {
            Route::post('/auth/logout', [PortalAuthController::class, 'logout']);
            Route::get('/dashboard', PortalDashboardController::class);

            Route::get('/invoices', [PortalInvoiceController::class, 'index']);
            Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show']);
            Route::get('/invoices/{invoice}/pdf', [PortalInvoiceController::class, 'pdf']);
            Route::post('/invoices/{invoice}/pay', [PortalPaymentController::class, 'pay']);
            Route::get('/invoices/{invoice}/payment-status', [PortalPaymentController::class, 'paymentStatus']);

            Route::get('/quotes', [PortalQuoteController::class, 'index']);
            Route::get('/quotes/{quote}', [PortalQuoteController::class, 'show']);
            Route::get('/quotes/{quote}/pdf', [PortalQuoteController::class, 'pdf']);
            Route::post('/quotes/{quote}/accept', [PortalQuoteController::class, 'accept']);
            Route::post('/quotes/{quote}/reject', [PortalQuoteController::class, 'reject']);
        });
    });

    Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('throttle:webhooks');

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
            Route::get('/portal', [PortalSettingsController::class, 'show']);
            Route::put('/portal', [PortalSettingsController::class, 'update']);
            Route::put('/currency', [UserSettingsController::class, 'updateCurrencySettings']);
            Route::put('/email', [UserSettingsController::class, 'updateEmailSettings']);
            Route::put('/sms', [UserSettingsController::class, 'updateSmsSettings']);
            Route::put('/notifications', [UserSettingsController::class, 'updateNotificationPreferences']);
            Route::get('/calendar', [UserSettingsController::class, 'calendarSettings']);
            Route::put('/calendar', [UserSettingsController::class, 'updateCalendarSettings']);

            // 2FA Management
            Route::post('/2fa/enable', [UserSettingsController::class, 'enable2fa']);
            Route::post('/2fa/confirm', [UserSettingsController::class, 'confirm2fa']);
            Route::post('/2fa/disable', [UserSettingsController::class, 'disable2fa']);
        });

        Route::get('export/full', [DataExportController::class, 'full']);
        Route::post('import/{entity}', [DataImportController::class, 'import']);
        Route::delete('account', AccountDeletionController::class);

        // Clients
        Route::get('clients/{client}/portal-access', [PortalAccessTokenController::class, 'index']);
        Route::post('clients/{client}/portal-access', [PortalAccessTokenController::class, 'store']);
        Route::delete('clients/{client}/portal-access/{portalAccessToken}', [PortalAccessTokenController::class, 'destroy']);
        Route::get('clients/{client}/portal-activity', [PortalAccessTokenController::class, 'logs']);
        Route::get('clients/export/csv', [ClientController::class, 'exportCsv']);
        Route::post('clients/import/csv', [ClientController::class, 'importCsv']);
        Route::post('clients/{client}/restore', [ClientController::class, 'restore']);
        Route::apiResource('clients', ClientController::class);

        // Projects
        Route::apiResource('projects', ProjectController::class);
        Route::prefix('projects/{project}')->group(function () {
            Route::post('generate-invoice', [ProjectInvoiceController::class, 'generate']);
            Route::get('expenses', [ProjectExpenseController::class, 'index']);

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

        // Expenses
        Route::apiResource('expense-categories', ExpenseCategoryController::class)->except(['show']);
        Route::get('expenses/report', [ExpenseReportController::class, 'report']);
        Route::get('expenses/report/export', [ExpenseReportController::class, 'export']);
        Route::post('expenses/{expense}/receipt', [ExpenseController::class, 'uploadReceipt']);
        Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt']);
        Route::apiResource('expenses', ExpenseController::class);

        // Client Contacts
        Route::apiResource('clients.contacts', ContactController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Segments
        Route::get('segments/{segment}/preview', [SegmentController::class, 'preview']);
        Route::apiResource('segments', SegmentController::class);

        // Campaigns
        Route::get('campaigns/compare', [CampaignAnalyticsController::class, 'compare']);
        Route::apiResource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send']);
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
        Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate']);
        Route::post('campaigns/{campaign}/test', [CampaignController::class, 'testSend']);
        Route::get('campaigns/{campaign}/analytics', [CampaignAnalyticsController::class, 'show']);
        Route::get('campaigns/{campaign}/analytics/export', [CampaignAnalyticsController::class, 'export']);
        Route::apiResource('campaign-templates', CampaignTemplateController::class)->only(['index', 'store', 'update', 'destroy']);

        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store']);

        // Recurring Invoices
        Route::apiResource('recurring-invoices', RecurringInvoiceProfileController::class);
        Route::post('recurring-invoices/{recurring_invoice}/pause', [RecurringInvoiceProfileController::class, 'pause']);
        Route::post('recurring-invoices/{recurring_invoice}/resume', [RecurringInvoiceProfileController::class, 'resume']);
        Route::post('recurring-invoices/{recurring_invoice}/cancel', [RecurringInvoiceProfileController::class, 'cancel']);

        // Quotes
        Route::apiResource('quotes', QuoteController::class);
        Route::post('quotes/{quote}/send', [QuoteController::class, 'send']);
        Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept']);
        Route::post('quotes/{quote}/reject', [QuoteController::class, 'reject']);
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf']);

        // Credit Notes
        Route::apiResource('credit-notes', CreditNoteController::class);
        Route::post('credit-notes/{credit_note}/send', [CreditNoteController::class, 'send']);
        Route::post('credit-notes/{credit_note}/apply', [CreditNoteController::class, 'apply']);
        Route::get('credit-notes/{credit_note}/pdf', [CreditNoteController::class, 'pdf']);

        // Currencies
        Route::get('currencies', [CurrencyController::class, 'index']);
        Route::get('currencies/rates', [CurrencyController::class, 'rates']);

        // Calendar
        Route::apiResource('calendar-connections', CalendarConnectionController::class);
        Route::post('calendar-connections/{calendar_connection}/test', [CalendarConnectionController::class, 'test']);
        Route::get('calendar-connections/google/callback', [CalendarConnectionController::class, 'googleCallback']);
        Route::apiResource('calendar-events', CalendarEventController::class);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('revenue', [ReportController::class, 'revenue']);
            Route::get('outstanding', [ReportController::class, 'outstanding']);
            Route::get('vat-summary', [ReportController::class, 'vatSummary']);
            Route::get('profit-loss', ProfitLossController::class);
            Route::get('project-profitability', ProjectProfitabilityController::class);
            Route::get('export', [ReportController::class, 'export']);
        });

        // Tags
        Route::post('clients/{client}/tags', [TagController::class, 'attachToClient']);
        Route::delete('clients/{client}/tags/{tag}', [TagController::class, 'detachFromClient']);
        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'destroy']);

        // Search & Activities
        Route::get('search', SearchController::class);
        Route::get('activities', [ActivityController::class, 'index']);

        // Phase 7 - Accounting
        Route::prefix('accounting')->group(function () {
            Route::get('/fec/count', [FecExportController::class, 'count']);
            Route::get('/fec', [FecExportController::class, 'export']);
            Route::get('/vat', [VatDeclarationController::class, 'index']);
            Route::get('/vat/export', [VatDeclarationController::class, 'exportCsv']);
            Route::get('/export/formats', [AccountingExportController::class, 'formats']);
            Route::get('/export', [AccountingExportController::class, 'export']);
            Route::get('/fiscal-year', [FiscalYearSummaryController::class, 'index']);
        });

        // Phase 7 - Settings (Accounting, API Tokens, Webhooks)
        Route::prefix('settings')->group(function () {
            Route::get('/accounting', [AccountingSettingsController::class, 'show']);
            Route::put('/accounting', [AccountingSettingsController::class, 'update']);

            Route::get('/api-tokens', [SettingsPersonalAccessTokenController::class, 'index']);
            Route::post('/api-tokens', [SettingsPersonalAccessTokenController::class, 'store']);
            Route::delete('/api-tokens/{id}', [SettingsPersonalAccessTokenController::class, 'destroy']);
            Route::get('/api-tokens/scopes', [SettingsPersonalAccessTokenController::class, 'scopes']);

            Route::get('/webhooks', [WebhookEndpointController::class, 'index']);
            Route::post('/webhooks', [WebhookEndpointController::class, 'store']);
            Route::get('/webhooks/{id}', [WebhookEndpointController::class, 'show']);
            Route::put('/webhooks/{id}', [WebhookEndpointController::class, 'update']);
            Route::delete('/webhooks/{id}', [WebhookEndpointController::class, 'destroy']);
            Route::post('/webhooks/{id}/test', [WebhookEndpointController::class, 'test']);
            Route::get('/webhooks/{id}/deliveries', [WebhookDeliveryController::class, 'index']);
            Route::get('/webhooks/{endpointId}/deliveries/{deliveryId}', [WebhookDeliveryController::class, 'show']);
            Route::post('/webhooks/{endpointId}/deliveries/{deliveryId}/retry', [WebhookDeliveryController::class, 'retry']);
        });

        // Phase 7 - Leads
        Route::get('leads/pipeline', [LeadPipelineController::class, 'index']);
        Route::get('leads/analytics', [LeadAnalyticsController::class, 'index']);
        Route::patch('leads/{id}/status', [LeadController::class, 'updateStatus']);
        Route::patch('leads/{id}/position', [LeadController::class, 'updatePosition']);
        Route::post('leads/{id}/convert', [LeadConversionController::class, 'convert']);
        Route::get('leads/{leadId}/activities', [LeadActivityController::class, 'index']);
        Route::post('leads/{leadId}/activities', [LeadActivityController::class, 'store']);
        Route::delete('leads/{leadId}/activities/{activityId}', [LeadActivityController::class, 'destroy']);
        Route::apiResource('leads', LeadController::class);

        // Documents (GED)
        Route::get('documents/stats', [DocumentController::class, 'stats']);
        Route::delete('documents/bulk', [DocumentController::class, 'bulkDestroy']);
        Route::apiResource('documents', DocumentController::class);
        Route::post('documents/{document}/reupload', [DocumentController::class, 'reupload']);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::post('documents/{document}/email', [DocumentController::class, 'sendEmail']);
    });
});
