<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LeadAnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LeadAnalyticsService $leadAnalyticsService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $analytics = $this->leadAnalyticsService->build($user);

        return $this->success($analytics, 'Lead analytics retrieved successfully');
    }
}
