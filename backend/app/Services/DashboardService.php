<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(protected FinancialSummaryService $financialSummaryService) {}

    /**
     * @return array<string, mixed>
     */
    public function getStats(User $user): array
    {
        $userId = $user->id;
        $cacheKey = "dashboard_stats_{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $userId) {
            $pendingStatuses = ['draft', 'sent', 'viewed', 'partially_paid', 'overdue'];

            $pendingInvoices = Invoice::query()
                ->where('user_id', $userId)
                ->whereIn('status', $pendingStatuses)
                ->get();

            $pendingAmount = round(
                (float) $pendingInvoices->sum(fn (Invoice $invoice): float => (float) $invoice->balance_due),
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

            $revenueMonth = $this->revenueForRange($userId, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
            $revenueQuarter = $this->revenueForRange($userId, now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString());
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
                'revenue_trend' => $monthlyBreakdown,
                'upcoming_deadlines' => $upcomingDeadlines,
            ];
        });
    }

    private function revenueForRange(string $userId, string $dateFrom, string $dateTo): float
    {
        return round((float) Invoice::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->sum('total'), 2);
    }
}
