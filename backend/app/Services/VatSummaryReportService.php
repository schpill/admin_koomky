<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class VatSummaryReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $cacheKey = sprintf(
            'report:vat:%s:%s',
            $user->id,
            md5(json_encode($filters, JSON_THROW_ON_ERROR))
        );

        return $this->rememberWithFallback($cacheKey, now()->addMinutes(15), function () use ($user, $filters): array {
            $invoiceQuery = Invoice::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['paid', 'partially_paid']);

            if (is_string($filters['date_from'] ?? null) && $filters['date_from'] !== '') {
                $invoiceQuery->whereDate('issue_date', '>=', $filters['date_from']);
            }

            if (is_string($filters['date_to'] ?? null) && $filters['date_to'] !== '') {
                $invoiceQuery->whereDate('issue_date', '<=', $filters['date_to']);
            }

            if (is_string($filters['client_id'] ?? null) && $filters['client_id'] !== '') {
                $invoiceQuery->where('client_id', $filters['client_id']);
            }

            if (is_string($filters['project_id'] ?? null) && $filters['project_id'] !== '') {
                $invoiceQuery->where('project_id', $filters['project_id']);
            }

            $invoiceIds = $invoiceQuery->pluck('id');

            if ($invoiceIds->isEmpty()) {
                return [
                    'filters' => $filters,
                    'total_vat' => 0.0,
                    'by_rate' => [],
                ];
            }

            $lineItems = LineItem::query()
                ->where('documentable_type', Invoice::class)
                ->whereIn('documentable_id', $invoiceIds)
                ->get();

            $totals = [];

            foreach ($lineItems as $lineItem) {
                $rate = (float) $lineItem->vat_rate;
                $rateKey = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
                $lineVat = round((float) $lineItem->total * ($rate / 100), 2);

                if (! array_key_exists($rateKey, $totals)) {
                    $totals[$rateKey] = [
                        'rate' => $rateKey,
                        'taxable_amount' => 0.0,
                        'vat_amount' => 0.0,
                    ];
                }

                $totals[$rateKey]['taxable_amount'] = round($totals[$rateKey]['taxable_amount'] + (float) $lineItem->total, 2);
                $totals[$rateKey]['vat_amount'] = round($totals[$rateKey]['vat_amount'] + $lineVat, 2);
            }

            $byRate = array_values($totals);

            usort($byRate, fn (array $a, array $b): int => strcmp((string) $a['rate'], (string) $b['rate']));

            return [
                'filters' => $filters,
                'total_vat' => round((float) array_sum(array_map(fn (array $row): float => (float) $row['vat_amount'], $byRate)), 2),
                'by_rate' => $byRate,
            ];
        });
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
