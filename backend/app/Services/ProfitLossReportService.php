<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProfitLossReportService
{
    public function __construct(
        protected CurrencyConversionService $currencyConversionService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

        $invoiceQuery = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->with(['project', 'client']);

        $expenseQuery = Expense::query()
            ->where('user_id', $user->id)
            ->with(['project', 'client']);

        $this->applyInvoiceFilters($invoiceQuery, $filters);
        $this->applyExpenseFilters($expenseQuery, $filters);

        /** @var Collection<int, Invoice> $invoices */
        $invoices = $invoiceQuery->orderBy('issue_date')->get();
        /** @var Collection<int, Expense> $expenses */
        $expenses = $expenseQuery->orderBy('date')->get();

        $revenue = round((float) $invoices->sum(
            fn (Invoice $invoice): float => $this->convertAmount(
                (float) $invoice->total,
                (string) $invoice->currency,
                $baseCurrency,
                $invoice->issue_date
            )
        ), 2);

        $expensesTotal = round((float) $expenses->sum(
            fn (Expense $expense): float => $this->expenseAmountInBaseCurrency($expense, $baseCurrency)
        ), 2);

        $profit = round($revenue - $expensesTotal, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;

        $monthlyRevenue = $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->issue_date->format('Y-m'))
            ->map(fn (Collection $items): float => round((float) $items->sum(
                fn (Invoice $invoice): float => $this->convertAmount(
                    (float) $invoice->total,
                    (string) $invoice->currency,
                    $baseCurrency,
                    $invoice->issue_date
                )
            ), 2));

        $monthlyExpenses = $expenses
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'))
            ->map(fn (Collection $items): float => round((float) $items->sum(
                fn (Expense $expense): float => $this->expenseAmountInBaseCurrency($expense, $baseCurrency)
            ), 2));

        /** @var Collection<int, string> $months */
        $months = $monthlyRevenue->keys()->merge($monthlyExpenses->keys())->unique()->sort()->values();

        $byMonth = $months
            ->map(function (string $month) use ($monthlyRevenue, $monthlyExpenses): array {
                $monthRevenue = (float) ($monthlyRevenue->get($month) ?? 0.0);
                $monthExpenses = (float) ($monthlyExpenses->get($month) ?? 0.0);

                return [
                    'month' => $month,
                    'revenue' => $monthRevenue,
                    'expenses' => $monthExpenses,
                    'profit' => round($monthRevenue - $monthExpenses, 2),
                ];
            })
            ->values()
            ->all();

        $projectKeys = $invoices
            ->pluck('project_id')
            ->filter(fn (?string $id): bool => is_string($id) && $id !== '')
            ->merge($expenses->pluck('project_id')->filter(fn (?string $id): bool => is_string($id) && $id !== ''))
            ->unique()
            ->values();

        $byProject = $projectKeys
            ->map(function (string $projectId) use ($invoices, $expenses, $baseCurrency): array {
                /** @var Collection<int, Invoice> $projectInvoices */
                $projectInvoices = $invoices->where('project_id', $projectId)->values();
                /** @var Collection<int, Expense> $projectExpenses */
                $projectExpenses = $expenses->where('project_id', $projectId)->values();

                $projectRevenue = round((float) $projectInvoices->sum(
                    fn (Invoice $invoice): float => $this->convertAmount(
                        (float) $invoice->total,
                        (string) $invoice->currency,
                        $baseCurrency,
                        $invoice->issue_date
                    )
                ), 2);
                $projectExpenseTotal = round((float) $projectExpenses->sum(
                    fn (Expense $expense): float => $this->expenseAmountInBaseCurrency($expense, $baseCurrency)
                ), 2);

                $project = $projectInvoices->first()?->project ?? $projectExpenses->first()?->project;

                return [
                    'project_id' => $projectId,
                    'project_reference' => $project?->reference,
                    'project_name' => $project?->name,
                    'revenue' => $projectRevenue,
                    'expenses' => $projectExpenseTotal,
                    'profit' => round($projectRevenue - $projectExpenseTotal, 2),
                ];
            })
            ->values()
            ->all();

        $clientKeys = $invoices
            ->pluck('client_id')
            ->filter(fn (?string $id): bool => is_string($id) && $id !== '')
            ->merge($expenses->pluck('client_id')->filter(fn (?string $id): bool => is_string($id) && $id !== ''))
            ->unique()
            ->values();

        $byClient = $clientKeys
            ->map(function (string $clientId) use ($invoices, $expenses, $baseCurrency): array {
                /** @var Collection<int, Invoice> $clientInvoices */
                $clientInvoices = $invoices->where('client_id', $clientId)->values();
                /** @var Collection<int, Expense> $clientExpenses */
                $clientExpenses = $expenses->where('client_id', $clientId)->values();

                $clientRevenue = round((float) $clientInvoices->sum(
                    fn (Invoice $invoice): float => $this->convertAmount(
                        (float) $invoice->total,
                        (string) $invoice->currency,
                        $baseCurrency,
                        $invoice->issue_date
                    )
                ), 2);
                $clientExpenseTotal = round((float) $clientExpenses->sum(
                    fn (Expense $expense): float => $this->expenseAmountInBaseCurrency($expense, $baseCurrency)
                ), 2);

                $client = $clientInvoices->first()?->client ?? $clientExpenses->first()?->client;

                return [
                    'client_id' => $clientId,
                    'client_name' => $client?->name,
                    'revenue' => $clientRevenue,
                    'expenses' => $clientExpenseTotal,
                    'profit' => round($clientRevenue - $clientExpenseTotal, 2),
                ];
            })
            ->values()
            ->all();

        return [
            'filters' => $filters,
            'base_currency' => $baseCurrency,
            'revenue' => $revenue,
            'expenses' => $expensesTotal,
            'profit' => $profit,
            'margin' => $margin,
            'by_month' => $byMonth,
            'by_project' => $byProject,
            'by_client' => $byClient,
        ];
    }

    /**
     * @param  Builder<Invoice>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyInvoiceFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
            $query->where('project_id', $filters['project_id']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }
    }

    /**
     * @param  Builder<Expense>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyExpenseFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
            $query->where('project_id', $filters['project_id']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }
    }

    private function expenseAmountInBaseCurrency(Expense $expense, string $baseCurrency): float
    {
        if (strtoupper((string) $expense->currency) === strtoupper($baseCurrency)) {
            return (float) $expense->amount;
        }

        if ($expense->base_currency_amount !== null) {
            return (float) $expense->base_currency_amount;
        }

        return $this->convertAmount(
            (float) $expense->amount,
            (string) $expense->currency,
            $baseCurrency,
            $expense->date
        );
    }

    private function convertAmount(float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date): float
    {
        return $this->currencyConversionService->convert(
            $amount,
            strtoupper($fromCurrency),
            strtoupper($toCurrency),
            $date
        );
    }
}
