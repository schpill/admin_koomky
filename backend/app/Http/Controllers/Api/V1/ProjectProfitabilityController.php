<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProjectProfitabilityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectProfitabilityController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProjectProfitabilityService $projectProfitabilityService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = $this->projectProfitabilityService->build($user, [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'client_id' => (string) $request->query('client_id', ''),
        ]);

        return $this->success($report, 'Project profitability report generated successfully');
    }
}
