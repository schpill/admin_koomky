<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for computing lead pipeline analytics.
 */
class LeadAnalyticsService
{
    /**
     * Build pipeline analytics.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(User $user, array $options = []): array
    {
        $dateFrom = isset($options['date_from'])
            ? Carbon::parse($options['date_from'])
            : now()->subYear();
        $dateTo = isset($options['date_to'])
            ? Carbon::parse($options['date_to'])
            : now();

        return [
            'total_pipeline_value' => $this->computeTotalPipelineValue($user),
            'leads_by_status' => $this->computeLeadsByStatus($user),
            'win_rate' => $this->computeWinRate($user, $dateFrom, $dateTo),
            'average_deal_value' => $this->computeAverageDealValue($user, $dateFrom, $dateTo),
            'average_time_to_close' => $this->computeAverageTimeToClose($user, $dateFrom, $dateTo),
            'pipeline_by_source' => $this->computePipelineBySource($user),
        ];
    }

    /**
     * Compute total pipeline value (open deals only).
     */
    private function computeTotalPipelineValue(User $user): float
    {
        return (float) Lead::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['won', 'lost'])
            ->sum('estimated_value');
    }

    /**
     * Compute leads count by status.
     *
     * @return array<string, int>
     */
    private function computeLeadsByStatus(User $user): array
    {
        $result = Lead::query()
            ->where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are represented
        $statuses = ['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost'];
        foreach ($statuses as $status) {
            if (! isset($result[$status])) {
                $result[$status] = 0;
            }
        }

        return $result;
    }

    /**
     * Compute win rate percentage.
     */
    private function computeWinRate(User $user, Carbon $dateFrom, Carbon $dateTo): float
    {
        $won = (int) Lead::query()
            ->where('user_id', $user->id)
            ->where('status', 'won')
            ->whereBetween('converted_at', [$dateFrom, $dateTo])
            ->count();

        $lost = (int) Lead::query()
            ->where('user_id', $user->id)
            ->where('status', 'lost')
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->count();

        $total = $won + $lost;

        if ($total === 0) {
            return 0.0;
        }

        return round(($won / $total) * 100, 2);
    }

    /**
     * Compute average deal value (won deals).
     */
    private function computeAverageDealValue(User $user, Carbon $dateFrom, Carbon $dateTo): float
    {
        $avg = Lead::query()
            ->where('user_id', $user->id)
            ->where('status', 'won')
            ->whereNotNull('estimated_value')
            ->whereBetween('converted_at', [$dateFrom, $dateTo])
            ->avg('estimated_value');

        return round((float) ($avg ?? 0), 2);
    }

    /**
     * Compute average time to close in days.
     */
    private function computeAverageTimeToClose(User $user, Carbon $dateFrom, Carbon $dateTo): float
    {
        $result = Lead::query()
            ->where('user_id', $user->id)
            ->where('status', 'won')
            ->whereNotNull('converted_at')
            ->whereBetween('converted_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (converted_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        return round((float) ($result ?? 0), 1);
    }

    /**
     * Compute pipeline by source.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computePipelineBySource(User $user): array
    {
        $results = DB::table('leads')
            ->where('user_id', $user->id)
            ->select('source', DB::raw('count(*) as lead_count'), DB::raw('sum(estimated_value) as total_value'))
            ->groupBy('source')
            ->get();

        return $results->map(fn ($item): array => [
            'source' => $item->source,
            'count' => (int) $item->lead_count,
            'total_value' => round((float) ($item->total_value ?? 0), 2),
        ])->toArray();
    }
}
