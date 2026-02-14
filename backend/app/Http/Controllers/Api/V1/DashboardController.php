<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(protected DashboardService $dashboardService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $stats = $this->dashboardService->getStats($request->user());

        return $this->success($stats, 'Dashboard statistics retrieved');
    }
}
