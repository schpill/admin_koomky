<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductAnalyticsService
{
    /**
     * Get analytics for a specific product.
     *
     * @return array<string, mixed>
     */
    public function productStats(Product $product, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = $product->sales();

        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }

        $confirmedQuery = (clone $query)->byStatus('confirmed');
        $pendingQuery = (clone $query)->byStatus('pending');

        $totalRevenue = (float) $confirmedQuery->sum('total_price');
        $totalSales = $confirmedQuery->count();
        $pendingSales = $pendingQuery->count();

        // Calculate average order value
        $avgOrderValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Calculate conversion rate (confirmed / (confirmed + pending))
        $totalQuotes = $confirmedQuery->whereNotNull('quote_id')->count();
        $convertedQuotes = $totalQuotes;
        $conversionRate = ($totalQuotes + $pendingSales) > 0
            ? ($convertedQuotes / ($totalQuotes + $pendingSales)) * 100
            : 0;

        // Monthly breakdown (last 12 months)
        $monthlyBreakdown = $this->getMonthlyBreakdown($product, $from, $to);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_sales' => $totalSales,
            'pending_sales' => $pendingSales,
            'avg_order_value' => round($avgOrderValue, 2),
            'conversion_rate' => round($conversionRate, 2),
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * Get global analytics for all products of a user.
     *
     * @return array<string, mixed>
     */
    public function globalStats(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = ProductSale::where('user_id', $user->id)->byStatus('confirmed');

        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }

        $totalRevenue = (float) $query->sum('total_price');
        $totalSales = $query->count();

        // Get top products
        $topProducts = $this->topProducts($user, 5, $from, $to);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_sales' => $totalSales,
            'top_products' => $topProducts->map(function ($product): array {
                $salesRevenue = (float) ($product->getAttribute('sales_revenue') ?? 0);
                $salesCount = (int) ($product->getAttribute('sales_count') ?? 0);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'revenue' => round($salesRevenue, 2),
                    'sales_count' => $salesCount,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get top products by revenue for a user.
     *
     * @return Collection<int, Product>
     */
    public function topProducts(User $user, int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        // Default to current month if no date range specified
        if (! $from) {
            $from = Carbon::now()->startOfMonth();
        }
        if (! $to) {
            $to = Carbon::now()->endOfMonth();
        }

        return Product::where('user_id', $user->id)
            ->withCount(['sales' => function ($query) use ($from, $to): void {
                $query->where('status', 'confirmed')
                    ->whereDate('sold_at', '>=', $from)
                    ->whereDate('sold_at', '<=', $to);
            }])
            ->withSum(['sales as sales_revenue' => function ($query) use ($from, $to): void {
                $query->where('status', 'confirmed')
                    ->whereDate('sold_at', '>=', $from)
                    ->whereDate('sold_at', '<=', $to);
            }], 'total_price')
            ->orderByDesc('sales_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get monthly breakdown for a product.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyBreakdown(Product $product, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $startDate = $from ?? Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = $to ?? Carbon::now()->endOfMonth();

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $monthRaw = DB::raw("strftime('%Y-%m', sold_at) as month");
        } else {
            $monthRaw = DB::raw("DATE_FORMAT(sold_at, '%Y-%m') as month");
        }

        $sales = $product->sales()
            ->byStatus('confirmed')
            ->whereDate('sold_at', '>=', $startDate)
            ->whereDate('sold_at', '<=', $endDate)
            ->select(
                $monthRaw,
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item): string {
                return (string) $item->getAttribute('month');
            });

        $breakdown = [];
        $currentDate = (clone $startDate)->startOfMonth();

        while ($currentDate <= $endDate) {
            $monthKey = $currentDate->format('Y-m');
            /** @var ProductSale|null $monthData */
            $monthData = $sales->get($monthKey);
            $monthRevenue = $monthData ? (float) ($monthData->getAttribute('revenue') ?? 0) : 0.0;
            $monthCount = $monthData ? (int) ($monthData->getAttribute('count') ?? 0) : 0;

            $breakdown[] = [
                'month' => $currentDate->format('M Y'),
                'month_iso' => $monthKey,
                'revenue' => round($monthRevenue, 2),
                'sales_count' => $monthCount,
            ];

            $currentDate->addMonth();
        }

        return $breakdown;
    }
}
