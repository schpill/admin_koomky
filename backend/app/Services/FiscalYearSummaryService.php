<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Service for computing fiscal year closing summaries.
 */
class FiscalYearSummaryService
{
    /**
     * Build fiscal year summary.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(User $user, array $options = []): array
    {
        $year = (int) ($options['year'] ?? now()->year);
        $fiscalYearStartMonth = $user->fiscal_year_start_month ?? 1;

        $dateFrom = Carbon::create($year, $fiscalYearStartMonth, 1)?->startOfMonth() ?? Carbon::now();

        // If fiscal year starts mid-year (e.g., July), the year spans two calendar years
        if ($fiscalYearStartMonth > 1) {
            $dateTo = Carbon::create($year + 1, $fiscalYearStartMonth - 1, 1)?->endOfMonth() ?? Carbon::now();
        } else {
            $dateTo = Carbon::create($year, 12, 31)?->endOfDay() ?? Carbon::now();
        }

        $revenue = $this->computeRevenue($user, $dateFrom, $dateTo);
        $expenses = $this->computeExpenses($user, $dateFrom, $dateTo);
        $netProfit = $revenue['total'] - $expenses['total'];
        $marginPercent = $revenue['total'] > 0 ? ($netProfit / $revenue['total']) * 100 : 0;
        $vatPosition = $this->computeVatPosition($user, $dateFrom, $dateTo);
        $outstanding = $this->computeOutstanding($user, $dateFrom, $dateTo);

        return [
            'year' => $year,
            'fiscal_year_start_month' => $fiscalYearStartMonth,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 2),
            'vat_position' => $vatPosition,
            'outstanding_receivables' => $outstanding,
        ];
    }

    /**
     * Compute revenue from paid invoices.
     *
     * @return array<string, mixed>
     */
    private function computeRevenue(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Get paid invoices in the period
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->whereHas('payments', function ($query) use ($dateFrom, $dateTo): void {
                $query->whereBetween('payment_date', [$dateFrom, $dateTo]);
            })
            ->with(['payments'])
            ->get();

        $totalPayments = 0;
        $invoiceCount = 0;

        foreach ($invoices as $invoice) {
            $periodPayments = $invoice->payments()
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->sum('amount');
            $totalPayments += (float) $periodPayments;
            $invoiceCount++;
        }

        // Also include sent/viewed invoices for accrual basis
        $accrualInvoices = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['sent', 'viewed', 'paid', 'partially_paid'])
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $accrualTotal = $accrualInvoices->sum(function ($invoice): float {
            return (float) ($invoice->base_currency_total ?? $invoice->total);
        });

        // Subtract credit notes
        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', 'applied')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $creditNoteTotal = $creditNotes->sum(function ($creditNote): float {
            return (float) ($creditNote->base_currency_total ?? $creditNote->total);
        });

        $netRevenue = (float) $accrualTotal - (float) $creditNoteTotal;

        return [
            'total' => round($netRevenue, 2),
            'by_currency' => $this->groupByCurrency($invoices),
            'invoice_count' => $invoiceCount,
            'cash_basis' => round($totalPayments, 2),
            'accrual_basis' => round($netRevenue, 2),
        ];
    }

    /**
     * Compute expenses.
     *
     * @return array<string, mixed>
     */
    private function computeExpenses(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['category'])
            ->get();

        $total = $expenses->sum(function ($expense): float {
            return (float) ($expense->base_currency_amount ?? $expense->amount);
        });

        $byCategory = $expenses->groupBy('expense_category_id')->map(function ($items): array {
            $firstItem = $items->first();
            $categoryName = $firstItem && $firstItem->category ? $firstItem->category->name : 'Sans catégorie';

            return [
                'category_name' => $categoryName,
                'total' => round($items->sum(function ($expense): float {
                    return (float) ($expense->base_currency_amount ?? $expense->amount);
                }), 2),
                'count' => $items->count(),
            ];
        })->values()->toArray();

        return [
            'total' => round($total, 2),
            'by_category' => $byCategory,
            'count' => $expenses->count(),
            'by_currency' => $this->groupExpensesByCurrency($expenses),
        ];
    }

    /**
     * Compute VAT position.
     *
     * @return array<string, mixed>
     */
    private function computeVatPosition(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        // TVA collectée
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $vatCollected = $invoices->sum('tax_amount');

        // Subtract credit note VAT
        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $vatCollected -= $creditNotes->sum('tax_amount');

        // TVA déductible
        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $vatDeductible = $expenses->sum('tax_amount');

        $netDue = (float) $vatCollected - (float) $vatDeductible;

        return [
            'vat_collected' => round((float) $vatCollected, 2),
            'vat_deductible' => round((float) $vatDeductible, 2),
            'net_due' => round($netDue, 2),
            'is_credit' => $netDue < 0,
        ];
    }

    /**
     * Compute outstanding receivables.
     *
     * @return array<string, mixed>
     */
    private function computeOutstanding(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Get unpaid/partially paid invoices
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['sent', 'viewed', 'partially_paid', 'overdue'])
            ->where('issue_date', '<=', $dateTo)
            ->with(['payments', 'client'])
            ->get();

        $total = 0;
        $overdue = 0;
        $details = [];

        foreach ($invoices as $invoice) {
            $invoiceTotal = (float) ($invoice->base_currency_total ?? $invoice->total);
            $amountPaid = (float) $invoice->payments->sum('amount');
            $balanceDue = max(0, $invoiceTotal - $amountPaid);

            if ($balanceDue > 0) {
                $total += $balanceDue;

                $isOverdue = $invoice->due_date < now();
                if ($isOverdue) {
                    $overdue += $balanceDue;
                }

                $details[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'client_name' => $invoice->client?->name,
                    'balance_due' => round($balanceDue, 2),
                    'due_date' => $invoice->due_date->toDateString(),
                    'is_overdue' => $isOverdue,
                ];
            }
        }

        return [
            'total' => round($total, 2),
            'overdue' => round($overdue, 2),
            'invoice_count' => count($details),
            'details' => $details,
        ];
    }

    /**
     * Group invoices by currency.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function groupByCurrency($invoices): array
    {
        return $invoices->groupBy('currency')->map(function ($items, $currency): array {
            return [
                'currency' => $currency,
                'total' => round($items->sum('total'), 2),
                'count' => $items->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Group expenses by currency.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Expense>  $expenses
     * @return array<int, array<string, mixed>>
     */
    private function groupExpensesByCurrency($expenses): array
    {
        return $expenses->groupBy('currency')->map(function ($items, $currency): array {
            return [
                'currency' => $currency,
                'total' => round($items->sum('amount'), 2),
                'count' => $items->count(),
            ];
        })->values()->toArray();
    }
}
