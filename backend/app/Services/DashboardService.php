<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getStats(User $user): array
    {
        $userId = $user->id;
        $cacheKey = "dashboard_stats_{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId) {
            return [
                'total_clients' => Client::where('user_id', $userId)->count(),
                'active_projects' => 0, // Placeholder for Sprint 4
                'pending_invoices_amount' => 0, // Placeholder for Sprint 4
                'recent_activities' => Activity::where('user_id', $userId)
                    ->latest()
                    ->take(5)
                    ->get()
            ];
        });
    }
}
