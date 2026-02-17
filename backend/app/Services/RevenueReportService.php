<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RevenueReportService
{
    public function __construct(protected CurrencyConversionService $currencyConversionService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $cacheKey = sprintf(
            'report:revenue:%s:%s',
            $user->id,
            md5(json_encode($filters, JSON_THROW_ON_ERROR))
        );

        return $this->rememberWithFallback($cacheKey, now()->addMinutes(15), function () use ($user, $filters): array {
            $query = Invoice::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['paid', 'partially_paid']);

            $this->applyFilters($query, $filters);

            /** @var Collection<int, Invoice> $invoices */
            $invoices = $query->orderBy('issue_date')->get();
            $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

            $byMonth = $invoices
                ->groupBy(fn (Invoice $invoice): string => $invoice->issue_date->format('Y-m'))
                ->map(function (Collection $monthInvoices, string $month) use ($baseCurrency): array {
                    $monthTotal = $monthInvoices->sum(function (Invoice $invoice) use ($baseCurrency): float {
                        return $this->currencyConversionService->convert(
                            (float) $invoice->total,
                            (string) $invoice->currency,
                            $baseCurrency,
                            $invoice->issue_date
                        );
                    });

                    return [
                        'month' => $month,
                        'total' => round((float) $monthTotal, 2),
                        'count' => $monthInvoices->count(),
                    ];
                })
                ->values()
                ->all();

            $currencyBreakdown = $invoices
                ->groupBy(fn (Invoice $invoice): string => strtoupper((string) $invoice->currency))
                ->map(fn (Collection $items): float => round((float) $items->sum(fn (Invoice $invoice): float => (float) $invoice->total), 2))
                ->toArray();

            $totalRevenue = $invoices->sum(function (Invoice $invoice) use ($baseCurrency): float {
                return $this->currencyConversionService->convert(
                    (float) $invoice->total,
                    (string) $invoice->currency,
                    $baseCurrency,
                    $invoice->issue_date
                );
            });

            return [
                'filters' => $filters,
                'base_currency' => $baseCurrency,
                'total_revenue' => round((float) $totalRevenue, 2),
                'count' => $invoices->count(),
                'currency_breakdown' => $currencyBreakdown,
                'by_month' => $byMonth,
            ];
        });
    }

    /**
     * @param  Builder<Invoice>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
            $query->where('project_id', $filters['project_id']);
        }
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
