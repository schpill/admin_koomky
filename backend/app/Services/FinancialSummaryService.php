<?php

namespace App\Services;

use App\Models\User;

class FinancialSummaryService
{
    public function __construct(protected RevenueReportService $revenueReportService) {}

    /**
     * @return array<string, mixed>
     */
    public function yearlySummary(User $user, ?int $year = null): array
    {
        $targetYear = $year ?? (int) now()->format('Y');

        $revenueData = $this->revenueReportService->build($user, [
            'date_from' => sprintf('%d-01-01', $targetYear),
            'date_to' => sprintf('%d-12-31', $targetYear),
        ]);

        $monthlyTotals = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%d-%02d', $targetYear, $month);
            $monthlyTotals[$monthKey] = 0.0;
        }

        foreach ($revenueData['by_month'] as $entry) {
            $month = (string) ($entry['month'] ?? '');
            if (array_key_exists($month, $monthlyTotals)) {
                $monthlyTotals[$month] = round((float) ($entry['total'] ?? 0), 2);
            }
        }

        return [
            'year' => $targetYear,
            'total_revenue' => round((float) ($revenueData['total_revenue'] ?? 0), 2),
            'monthly_breakdown' => array_map(
                fn (string $month, float $total): array => ['month' => $month, 'total' => $total],
                array_keys($monthlyTotals),
                array_values($monthlyTotals)
            ),
        ];
    }
}
