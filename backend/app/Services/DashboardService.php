<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ProductSale;
use App\Models\Project;
use App\Models\RecurringInvoiceProfile;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardService
{
    public function __construct(
        protected FinancialSummaryService $financialSummaryService,
        protected CampaignAnalyticsService $campaignAnalyticsService,
        protected CurrencyConversionService $currencyConversionService,
        protected ProfitLossReportService $profitLossReportService,
        protected ExpenseReportService $expenseReportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getStats(User $user): array
    {
        $userId = $user->id;
        $cacheKey = "dashboard_stats_{$userId}";

        return $this->rememberWithFallback($cacheKey, now()->addMinutes(5), function () use ($user, $userId) {
            $pendingStatuses = ['draft', 'sent', 'viewed', 'partially_paid', 'overdue'];

            $pendingInvoices = Invoice::query()
                ->where('user_id', $userId)
                ->whereIn('status', $pendingStatuses)
                ->get();

            $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

            $pendingAmount = round(
                (float) $pendingInvoices->sum(fn (Invoice $invoice): float => $this->currencyConversionService->convert(
                    (float) $invoice->balance_due,
                    (string) $invoice->currency,
                    $baseCurrency,
                    $invoice->issue_date
                )),
                2
            );

            $overdueCount = Invoice::query()
                ->where('user_id', $userId)
                ->where('status', 'overdue')
                ->count();

            $pendingCount = Invoice::query()
                ->where('user_id', $userId)
                ->whereIn('status', ['sent', 'viewed', 'partially_paid', 'overdue'])
                ->count();

            $yearlySummary = $this->financialSummaryService->yearlySummary($user);
            $monthlyBreakdown = $yearlySummary['monthly_breakdown'] ?? [];

            $revenueMonth = $this->revenueForRange($user, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
            $revenueQuarter = $this->revenueForRange($user, now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString());
            $revenueYear = round((float) ($yearlySummary['total_revenue'] ?? 0), 2);

            $upcomingDeadlines = Project::query()
                ->where('user_id', $userId)
                ->whereIn('status', ['draft', 'proposal_sent', 'in_progress', 'on_hold'])
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('deadline')
                ->with('client')
                ->get()
                ->map(function (Project $project): array {
                    return [
                        'id' => $project->id,
                        'reference' => $project->reference,
                        'name' => $project->name,
                        'status' => $project->status,
                        'deadline' => $project->deadline?->toDateString(),
                        'client_id' => $project->client_id,
                        'client_name' => $project->client?->name,
                    ];
                })
                ->values()
                ->all();

            $activeRecurringProfiles = RecurringInvoiceProfile::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->with('client')
                ->orderBy('next_due_date')
                ->get();

            $recurringUpcomingProfiles = $activeRecurringProfiles
                ->take(5)
                ->map(function (RecurringInvoiceProfile $profile): array {
                    return [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'frequency' => $profile->frequency,
                        'next_due_date' => $profile->next_due_date->toDateString(),
                        'client_id' => $profile->client_id,
                        'client_name' => $profile->client?->name,
                    ];
                })
                ->values()
                ->all();

            $recurringEstimatedRevenue = round(
                (float) $activeRecurringProfiles->sum(
                    fn (RecurringInvoiceProfile $profile): float => $this->estimateMonthlyRecurringRevenue($profile)
                ),
                2
            );

            $campaignsLast30Days = Campaign::query()
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            $activeCampaignsCount = $campaignsLast30Days
                ->whereIn('status', ['scheduled', 'sending', 'paused'])
                ->count();

            $campaignRates = $campaignsLast30Days->map(function (Campaign $campaign): array {
                $metrics = $this->campaignAnalyticsService->forCampaign($campaign);

                return [
                    'open_rate' => (float) $metrics['open_rate'],
                    'click_rate' => (float) $metrics['click_rate'],
                ];
            });

            $averageOpenRate = $campaignRates->count() > 0
                ? round($campaignRates->avg('open_rate') ?? 0, 2)
                : 0.0;
            $averageClickRate = $campaignRates->count() > 0
                ? round($campaignRates->avg('click_rate') ?? 0, 2)
                : 0.0;

            $monthDateFrom = now()->startOfMonth()->toDateString();
            $monthDateTo = now()->endOfMonth()->toDateString();

            $profitLossSummary = $this->profitLossReportService->build($user, [
                'date_from' => $monthDateFrom,
                'date_to' => $monthDateTo,
            ]);

            $expenseSummary = $this->expenseReportService->build($user, [
                'date_from' => $monthDateFrom,
                'date_to' => $monthDateTo,
            ]);

            $rawExpenseCategories = $expenseSummary['by_category'] ?? [];
            if (! is_array($rawExpenseCategories)) {
                $rawExpenseCategories = [];
            }

            $topExpenseCategories = collect($rawExpenseCategories)
                ->sortByDesc('total')
                ->take(3)
                ->values()
                ->all();

            return [
                'total_clients' => Client::where('user_id', $userId)->count(),
                'active_projects' => Project::query()
                    ->where('user_id', $userId)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'pending_invoices_amount' => $pendingAmount,
                'recent_activities' => Activity::where('user_id', $userId)
                    ->latest()
                    ->take(5)
                    ->get(),

                'revenue_month' => $revenueMonth,
                'revenue_quarter' => $revenueQuarter,
                'revenue_year' => $revenueYear,
                'pending_invoices_count' => $pendingCount,
                'overdue_invoices_count' => $overdueCount,
                'base_currency' => $baseCurrency,
                'revenue_trend' => $monthlyBreakdown,
                'upcoming_deadlines' => $upcomingDeadlines,
                'recurring_profiles_active_count' => $activeRecurringProfiles->count(),
                'recurring_upcoming_due_profiles' => $recurringUpcomingProfiles,
                'recurring_estimated_revenue_month' => $recurringEstimatedRevenue,
                'active_campaigns_count' => $activeCampaignsCount,
                'average_campaign_open_rate' => $averageOpenRate,
                'average_campaign_click_rate' => $averageClickRate,
                'profit_loss_summary' => [
                    'revenue' => (float) ($profitLossSummary['revenue'] ?? 0),
                    'expenses' => (float) ($profitLossSummary['expenses'] ?? 0),
                    'profit' => (float) ($profitLossSummary['profit'] ?? 0),
                    'margin' => (float) ($profitLossSummary['margin'] ?? 0),
                    'base_currency' => $baseCurrency,
                ],
                'expense_overview' => [
                    'month_total' => (float) ($expenseSummary['total_expenses'] ?? 0),
                    'billable_total' => (float) ($expenseSummary['billable_split']['billable'] ?? 0),
                    'non_billable_total' => (float) ($expenseSummary['billable_split']['non_billable'] ?? 0),
                    'top_categories' => $topExpenseCategories,
                    'base_currency' => $baseCurrency,
                ],
                'overdue_invoices_widget' => $this->overdueInvoicesWidget($user),
                'time_tracked_today_widget' => $this->timeTrackedTodayWidget($user),
            ];
        });
    }

    /**
     * @return array{count:int,total_amount:float,currency:string}
     */
    public function overdueInvoicesWidget(User $user): array
    {
        $query = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', 'overdue')
            ->whereHas('reminderSchedule', function ($scheduleQuery): void {
                $scheduleQuery->whereNull('completed_at')->where('is_paused', false);
            });

        return [
            'count' => (int) $query->count(),
            'total_amount' => round((float) $query->sum('total'), 2),
            'currency' => strtoupper((string) ($user->base_currency ?? 'EUR')),
        ];
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function rememberWithFallback(string $key, \DateTimeInterface|\DateInterval|int|null $ttl, Closure $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $exception) {
            Log::warning('cache_fallback_activated', [
                'key' => $key,
                'reason' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }

    private function revenueForRange(User $user, string $dateFrom, string $dateTo): float
    {
        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $sum = $invoices->sum(function (Invoice $invoice) use ($baseCurrency): float {
            return $this->currencyConversionService->convert(
                (float) $invoice->total,
                (string) $invoice->currency,
                $baseCurrency,
                $invoice->issue_date
            );
        });

        return round((float) $sum, 2);
    }

    private function estimateMonthlyRecurringRevenue(RecurringInvoiceProfile $profile): float
    {
        $lineItems = collect($profile->line_items);

        $subtotal = (float) $lineItems->sum(function (array $line): float {
            return ((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_price'] ?? 0));
        });

        $discountPercent = (float) ($profile->discount_percent ?? 0);
        $afterDiscount = $subtotal - ($subtotal * ($discountPercent / 100));

        $multiplier = match ($profile->frequency) {
            'weekly' => 52 / 12,
            'biweekly' => 26 / 12,
            'monthly' => 1.0,
            'quarterly' => 1 / 3,
            'semiannual' => 1 / 6,
            'annual' => 1 / 12,
            default => 1.0,
        };

        return round($afterDiscount * $multiplier, 2);
    }

    /**
     * Get time tracked today widget data.
     *
     * @return array<string, mixed>
     */
    public function timeTrackedTodayWidget(User $user): array
    {
        $today = now()->toDateString();

        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('is_running', false)
            ->get();

        $minutesToday = (int) $entries->sum('duration_minutes');
        $entriesCount = $entries->count();

        return [
            'minutes_today' => $minutesToday,
            'entries_count' => $entriesCount,
        ];
    }

    /**
     * Get top products widget data for the current month.
     *
     * @return array<string, mixed>
     */
    public function topProductsWidget(User $user): array
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $topProducts = ProductSale::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereBetween('sold_at', [$from, $to])
            ->with('product')
            ->selectRaw('product_id, SUM(total_price) as revenue, COUNT(*) as sales_count')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get()
            ->map(function (ProductSale $sale): array {
                $salesCount = (int) ($sale->getAttribute('sales_count') ?? 0);
                $revenue = (float) ($sale->getAttribute('revenue') ?? 0);
                $product = $sale->product;

                return [
                    'id' => $sale->product_id,
                    'name' => $product ? $product->name : 'Produit supprimé',
                    'type' => $product ? $product->type->value : 'unknown',
                    'sales_count' => $salesCount,
                    'revenue' => round($revenue, 2),
                    'currency_code' => $product ? $product->currency_code : 'EUR',
                ];
            })
            ->toArray();

        $totalRevenue = ProductSale::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereBetween('sold_at', [$from, $to])
            ->sum('total_price');

        $totalSales = ProductSale::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereBetween('sold_at', [$from, $to])
            ->count();

        return [
            'products' => $topProducts,
            'total_revenue' => round((float) $totalRevenue, 2),
            'total_sales' => $totalSales,
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
        ];
    }
}
