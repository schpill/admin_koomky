<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Service for computing VAT declaration reports.
 *
 * Computes TVA collectée (collected VAT) and TVA déductible (deductible VAT)
 * for French VAT declaration (CA3-style).
 */
class VatDeclarationService
{
    /**
     * VAT rates used in France.
     */
    private const VAT_RATES = [0, 5.5, 10, 20];

    /**
     * Build a VAT declaration report for the given period.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(User $user, array $options = []): array
    {
        $periodType = $options['period_type'] ?? 'monthly';
        $year = (int) ($options['year'] ?? now()->year);

        if ($periodType === 'quarterly') {
            return $this->buildQuarterlyReport($user, $year);
        }

        return $this->buildMonthlyReport($user, $year);
    }

    /**
     * Build a monthly VAT report.
     *
     * @return array<string, mixed>
     */
    private function buildMonthlyReport(User $user, int $year): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $dateFrom = Carbon::create($year, $month, 1)?->startOfMonth() ?? Carbon::now();
            $dateTo = Carbon::create($year, $month, 1)?->endOfMonth() ?? Carbon::now();

            $months[] = array_merge(
                ['month' => $month, 'period' => $dateFrom->format('Y-m')],
                $this->computePeriodVat($user, $dateFrom, $dateTo)
            );
        }

        $totals = $this->computeTotals($months);

        return [
            'year' => $year,
            'period_type' => 'monthly',
            'periods' => $months,
            'totals' => $totals,
        ];
    }

    /**
     * Build a quarterly VAT report.
     *
     * @return array<string, mixed>
     */
    private function buildQuarterlyReport(User $user, int $year): array
    {
        $quarters = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $dateFrom = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)?->startOfMonth() ?? Carbon::now();
            $dateTo = Carbon::create($year, $quarter * 3, 1)?->endOfMonth() ?? Carbon::now();

            $quarters[] = array_merge(
                [
                    'quarter' => $quarter,
                    'period' => 'Q'.$quarter.' '.$year,
                    'months' => [
                        ($quarter - 1) * 3 + 1,
                        ($quarter - 1) * 3 + 2,
                        ($quarter - 1) * 3 + 3,
                    ],
                ],
                $this->computePeriodVat($user, $dateFrom, $dateTo)
            );
        }

        $totals = $this->computeTotals($quarters);

        return [
            'year' => $year,
            'period_type' => 'quarterly',
            'periods' => $quarters,
            'totals' => $totals,
        ];
    }

    /**
     * Compute VAT for a specific period.
     *
     * @return array<string, mixed>
     */
    private function computePeriodVat(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        $vatCollected = $this->computeVatCollected($user, $dateFrom, $dateTo);
        $vatDeductible = $this->computeVatDeductible($user, $dateFrom, $dateTo);

        $totalCollected = array_sum($vatCollected);
        $totalDeductible = array_sum(array_column($vatDeductible, 'amount'));
        $netDue = $totalCollected - $totalDeductible;

        return [
            'vat_collected' => $vatCollected,
            'vat_deductible' => $vatDeductible,
            'total_collected' => round($totalCollected, 2),
            'total_deductible' => round($totalDeductible, 2),
            'net_due' => round($netDue, 2),
            'is_credit' => $netDue < 0,
        ];
    }

    /**
     * Compute TVA collectée from invoices and credit notes.
     *
     * @return array<float>
     */
    private function computeVatCollected(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        $vatByRate = array_fill_keys(array_map('strval', self::VAT_RATES), 0.0);

        // Get VAT from invoices
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with('lineItems')
            ->get();

        foreach ($invoices as $invoice) {
            // Group line items by VAT rate
            foreach ($invoice->lineItems as $item) {
                $rate = (string) (float) $item->vat_rate;
                $itemVat = ((float) $item->total * (float) $item->vat_rate) / 100;
                if (isset($vatByRate[$rate])) {
                    $vatByRate[$rate] += $itemVat;
                } else {
                    $vatByRate[$rate] = $itemVat;
                }
            }
        }

        // Subtract VAT from credit notes
        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with('lineItems')
            ->get();

        foreach ($creditNotes as $creditNote) {
            foreach ($creditNote->lineItems as $item) {
                $rate = (string) (float) $item->vat_rate;
                $itemVat = ((float) $item->total * (float) $item->vat_rate) / 100;
                if (isset($vatByRate[$rate])) {
                    $vatByRate[$rate] -= $itemVat;
                }
            }
        }

        // Round all values
        foreach ($vatByRate as $rate => $amount) {
            $vatByRate[$rate] = round($amount, 2);
        }

        return $vatByRate;
    }

    /**
     * Compute TVA déductible from expenses.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computeVatDeductible(User $user, Carbon $dateFrom, Carbon $dateTo): array
    {
        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('tax_amount', '>', 0)
            ->get();

        $totalVat = $expenses->sum('tax_amount');

        return [
            [
                'type' => 'goods',
                'label' => 'TVA déductible sur biens',
                'amount' => round((float) $totalVat, 2),
            ],
        ];
    }

    /**
     * Compute totals from period data.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @return array<string, mixed>
     */
    private function computeTotals(array $periods): array
    {
        $totalCollected = 0.0;
        $totalDeductible = 0.0;

        foreach ($periods as $period) {
            $totalCollected += (float) ($period['total_collected'] ?? 0);
            $totalDeductible += (float) ($period['total_deductible'] ?? 0);
        }

        return [
            'total_collected' => round($totalCollected, 2),
            'total_deductible' => round($totalDeductible, 2),
            'net_due' => round($totalCollected - $totalDeductible, 2),
        ];
    }

    /**
     * Export VAT report to CSV format.
     *
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report): string
    {
        $lines = [];

        // Header
        $lines[] = $report['period_type'] === 'monthly'
            ? 'Period;VAT 0%;VAT 5.5%;VAT 10%;VAT 20%;Total Collected;Total Deductible;Net Due'
            : 'Quarter;VAT 0%;VAT 5.5%;VAT 10%;VAT 20%;Total Collected;Total Deductible;Net Due';

        foreach ($report['periods'] as $period) {
            $periodLabel = $report['period_type'] === 'monthly'
                ? $period['period']
                : $period['period'];

            $lines[] = sprintf(
                '%s;%s;%s;%s;%s;%s;%s;%s',
                $periodLabel,
                $this->formatAmount((float) ($period['vat_collected']['0'] ?? 0)),
                $this->formatAmount((float) ($period['vat_collected']['5.5'] ?? 0)),
                $this->formatAmount((float) ($period['vat_collected']['10'] ?? 0)),
                $this->formatAmount((float) ($period['vat_collected']['20'] ?? 0)),
                $this->formatAmount((float) $period['total_collected']),
                $this->formatAmount((float) $period['total_deductible']),
                $this->formatAmount((float) $period['net_due'])
            );
        }

        // Totals
        $lines[] = sprintf(
            'TOTAL;%s;%s;%s;%s;%s;%s;%s',
            '',
            '',
            '',
            '',
            $this->formatAmount((float) $report['totals']['total_collected']),
            $this->formatAmount((float) $report['totals']['total_deductible']),
            $this->formatAmount((float) $report['totals']['net_due'])
        );

        return implode("\n", $lines);
    }

    /**
     * Format amount with French decimal notation.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }
}
