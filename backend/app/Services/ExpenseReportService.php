<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExpenseReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $query = Expense::query()
            ->where('user_id', $user->id)
            ->with(['category', 'project', 'client'])
            ->orderByDesc('date');

        $this->applyFilters($query, $filters);

        /** @var Collection<int, Expense> $expenses */
        $expenses = $query->get();

        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

        $total = round((float) $expenses->sum(fn (Expense $expense): float => $this->getAmountInBaseCurrency($expense, $baseCurrency)), 2);

        // Assuming tax is in the same currency as the amount, so we convert it too if needed.
        $taxTotal = round((float) $expenses->sum(function (Expense $expense) use ($baseCurrency) {
            $rate = $this->getConversionRate($expense, $baseCurrency);

            return (float) $expense->tax_amount * $rate;
        }), 2);

        $byCategory = $expenses
            ->groupBy(fn (Expense $expense): string => (string) data_get($expense, 'category.name', 'Uncategorized'))
            ->map(fn (Collection $items, string $name): array => [
                'category' => $name,
                'total' => round((float) $items->sum(fn (Expense $expense): float => $this->getAmountInBaseCurrency($expense, $baseCurrency)), 2),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $byProject = $expenses
            ->whereNotNull('project_id')
            ->groupBy(fn (Expense $expense): string => (string) data_get($expense, 'project.reference', 'unassigned'))
            ->map(fn (Collection $items, string $reference): array => [
                'project_reference' => $reference,
                'project_name' => $items->first()?->project?->name,
                'total' => round((float) $items->sum(fn (Expense $expense): float => $this->getAmountInBaseCurrency($expense, $baseCurrency)), 2),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $billableTotal = round((float) $expenses
            ->filter(fn (Expense $expense): bool => $expense->is_billable)
            ->sum(fn (Expense $expense): float => $this->getAmountInBaseCurrency($expense, $baseCurrency)), 2);
        $nonBillableTotal = round($total - $billableTotal, 2);

        $monthly = $expenses
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'))
            ->map(fn (Collection $items, string $month): array => [
                'month' => $month,
                'total' => round((float) $items->sum(fn (Expense $expense): float => $this->getAmountInBaseCurrency($expense, $baseCurrency)), 2),
            ])
            ->sortBy('month')
            ->values()
            ->all();

        return [
            'filters' => $filters,
            'base_currency' => $baseCurrency,
            'total_expenses' => $total,
            'tax_total' => $taxTotal,
            'count' => $expenses->count(),
            'billable_split' => [
                'billable' => $billableTotal,
                'non_billable' => $nonBillableTotal,
            ],
            'by_category' => $byCategory,
            'by_project' => $byProject,
            'by_month' => $monthly,
            'items' => $expenses->map(fn (Expense $expense): array => [
                'id' => $expense->id,
                'date' => $expense->date->toDateString(),
                'description' => $expense->description,
                'category' => $expense->category?->name,
                'project_reference' => $expense->project?->reference,
                'client_name' => $expense->client?->name,
                'amount' => (float) $expense->amount,
                'currency' => $expense->currency,
                'base_currency_amount' => $this->getAmountInBaseCurrency($expense, $baseCurrency),
                'tax_amount' => (float) $expense->tax_amount,
                'is_billable' => $expense->is_billable,
                'status' => $expense->status,
            ])->values()->all(),
        ];
    }

    private function getAmountInBaseCurrency(Expense $expense, string $baseCurrency): float
    {
        if (strtoupper($expense->currency) === $baseCurrency) {
            return (float) $expense->amount;
        }

        // base_currency_amount should have been calculated on creation.
        // If not, we log an error and return the raw amount to avoid breaking the report.
        if ($expense->base_currency_amount === null) {
            logs()->error('Expense missing base_currency_amount', ['expense_id' => $expense->id]);

            return (float) $expense->amount;
        }

        return (float) $expense->base_currency_amount;
    }

    private function getConversionRate(Expense $expense, string $baseCurrency): float
    {
        if (strtoupper($expense->currency) === $baseCurrency) {
            return 1.0;
        }
        if ($expense->amount == 0) {
            return 1.0;
        }
        if ($expense->base_currency_amount === null) {
            return 1.0; // Cannot determine rate
        }

        return (float) $expense->base_currency_amount / (float) $expense->amount;
    }

    /**
     * @param  Builder<Expense>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (is_string($filters['expense_category_id'] ?? null) && $filters['expense_category_id'] !== '') {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }

        if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
            $query->where('project_id', $filters['project_id']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        if (array_key_exists('billable', $filters) && $filters['billable'] !== '' && $filters['billable'] !== null) {
            $query->where('is_billable', (bool) $filters['billable']);
        }

        if (is_string($filters['status'] ?? null) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }
}
