<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;

class OutstandingReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $query = Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['sent', 'viewed', 'partially_paid', 'overdue'])
            ->with('client');

        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        /** @var Collection<int, Invoice> $invoices */
        $invoices = $query->orderBy('due_date')->get();

        $items = $invoices->map(function (Invoice $invoice): array {
            $agingDays = (int) max(0, now()->diffInDays($invoice->due_date, false) * -1);

            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'client_name' => $invoice->client?->name,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date->toDateString(),
                'aging_days' => $agingDays,
                'aging_bucket' => $this->resolveAgingBucket($agingDays),
                'balance_due' => (float) $invoice->balance_due,
                'total' => (float) $invoice->total,
            ];
        })->filter(fn (array $item): bool => (float) $item['balance_due'] > 0)->values();

        $agingBuckets = [
            '0_30' => ['count' => 0, 'amount' => 0.0],
            '31_60' => ['count' => 0, 'amount' => 0.0],
            '61_90' => ['count' => 0, 'amount' => 0.0],
            '90_plus' => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($items as $item) {
            $bucket = (string) $item['aging_bucket'];
            if (! array_key_exists($bucket, $agingBuckets)) {
                $agingBuckets[$bucket] = ['count' => 0, 'amount' => 0.0];
            }

            $currentCount = (int) $agingBuckets[$bucket]['count'];
            $currentAmount = (float) $agingBuckets[$bucket]['amount'];

            $agingBuckets[$bucket] = [
                'count' => $currentCount + 1,
                'amount' => round($currentAmount + (float) $item['balance_due'], 2),
            ];
        }

        return [
            'filters' => $filters,
            'total_outstanding' => round((float) $items->sum(fn (array $item): float => (float) $item['balance_due']), 2),
            'total_invoices' => $items->count(),
            'aging' => $agingBuckets,
            'items' => $items->all(),
        ];
    }

    private function resolveAgingBucket(int $agingDays): string
    {
        if ($agingDays <= 30) {
            return '0_30';
        }

        if ($agingDays <= 60) {
            return '31_60';
        }

        if ($agingDays <= 90) {
            return '61_90';
        }

        return '90_plus';
    }
}
