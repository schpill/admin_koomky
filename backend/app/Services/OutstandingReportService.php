<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutstandingReportService
{
    public function __construct(protected CurrencyConversionService $currencyConversionService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $cacheKey = sprintf(
            'report:outstanding:%s:%s',
            $user->id,
            md5(json_encode($filters, JSON_THROW_ON_ERROR))
        );

        return $this->rememberWithFallback($cacheKey, now()->addMinutes(10), function () use ($user, $filters): array {
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
            $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

            $items = $invoices->map(function (Invoice $invoice) use ($baseCurrency): array {
                $agingDays = (int) max(0, now()->diffInDays($invoice->due_date, false) * -1);
                $balanceDue = (float) $invoice->balance_due;
                $balanceDueBase = $this->currencyConversionService->convert(
                    $balanceDue,
                    (string) $invoice->currency,
                    $baseCurrency,
                    $invoice->issue_date
                );

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'client_name' => $invoice->client?->name,
                    'status' => $invoice->status,
                    'currency' => $invoice->currency,
                    'due_date' => $invoice->due_date->toDateString(),
                    'aging_days' => $agingDays,
                    'aging_bucket' => $this->resolveAgingBucket($agingDays),
                    'balance_due' => $balanceDue,
                    'balance_due_base' => $balanceDueBase,
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
                    'amount' => round($currentAmount + (float) $item['balance_due_base'], 2),
                ];
            }

            return [
                'filters' => $filters,
                'base_currency' => $baseCurrency,
                'total_outstanding' => round((float) $items->sum(fn (array $item): float => (float) $item['balance_due_base']), 2),
                'total_invoices' => $items->count(),
                'aging' => $agingBuckets,
                'items' => $items->all(),
            ];
        });
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
}
