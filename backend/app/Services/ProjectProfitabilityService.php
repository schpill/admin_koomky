<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectProfitabilityService
{
    public function __construct(
        protected CurrencyConversionService $currencyConversionService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function build(User $user, array $filters = []): array
    {
        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

        $projectsQuery = Project::query()
            ->where('user_id', $user->id)
            ->with('client');

        if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
            $projectsQuery->where('id', $filters['project_id']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $projectsQuery->where('client_id', $filters['client_id']);
        }

        /** @var Collection<int, Project> $projects */
        $projects = $projectsQuery->get();

        return $projects
            ->map(function (Project $project) use ($user, $filters, $baseCurrency): array {
                $invoiceQuery = Invoice::query()
                    ->where('user_id', $user->id)
                    ->where('project_id', $project->id)
                    ->whereIn('status', ['paid', 'partially_paid']);

                $this->applyInvoiceDateFilters($invoiceQuery, $filters);

                /** @var Collection<int, Invoice> $invoices */
                $invoices = $invoiceQuery->get();
                $revenue = round((float) $invoices->sum(
                    fn (Invoice $invoice): float => $this->convertAmount(
                        (float) $invoice->total,
                        (string) $invoice->currency,
                        $baseCurrency,
                        $invoice->issue_date
                    )
                ), 2);

                $timeEntryQuery = TimeEntry::query()
                    ->where('user_id', $user->id)
                    ->whereHas('task', function (Builder $builder) use ($project): void {
                        $builder->where('project_id', $project->id);
                    });

                $this->applyTimeEntryDateFilters($timeEntryQuery, $filters);

                $minutes = (int) $timeEntryQuery->sum('duration_minutes');
                $hours = round($minutes / 60, 2);
                $hourlyRate = (float) ($project->hourly_rate ?? 0);
                $timeCost = round($hours * $hourlyRate, 2);

                $expenseQuery = Expense::query()
                    ->where('user_id', $user->id)
                    ->where('project_id', $project->id);

                $this->applyExpenseDateFilters($expenseQuery, $filters);

                /** @var Collection<int, Expense> $expenses */
                $expenses = $expenseQuery->get();
                $expenseTotal = round((float) $expenses->sum(
                    fn (Expense $expense): float => $this->expenseAmountInBaseCurrency($expense, $baseCurrency)
                ), 2);

                $profit = round($revenue - $timeCost - $expenseTotal, 2);
                $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;

                return [
                    'project_id' => $project->id,
                    'project_reference' => $project->reference,
                    'project_name' => $project->name,
                    'client_id' => $project->client_id,
                    'client_name' => $project->client?->name,
                    'currency' => $baseCurrency,
                    'hours' => $hours,
                    'revenue' => $revenue,
                    'time_cost' => $timeCost,
                    'expenses' => $expenseTotal,
                    'profit' => $profit,
                    'margin' => $margin,
                ];
            })
            ->sortByDesc('profit')
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Invoice>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyInvoiceDateFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }
    }

    /**
     * @param  Builder<TimeEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyTimeEntryDateFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
    }

    /**
     * @param  Builder<Expense>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyExpenseDateFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('date', '<=', $filters['date_to']);
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
