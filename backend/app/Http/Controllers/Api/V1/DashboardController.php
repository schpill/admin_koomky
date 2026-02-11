<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and recent activity.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total_clients' => $user->clients()->count(),
            'active_projects' => 0, // Will be implemented in Sprint 5
            'pending_tasks' => 0, // Will be implemented in Sprint 5
            'monthly_revenue' => 0, // Will be implemented in Sprint 6
        ];

        $recentActivities = Activity::query()
            ->where('user_id', $user->id)
            ->with(['subject', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'type' => 'dashboard',
                'attributes' => [
                    'stats' => $stats,
                    'recent_activity' => ActivityResource::collection($recentActivities),
                ],
            ],
        ]);
    }
}
