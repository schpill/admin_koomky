<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfitLossReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitLossController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProfitLossReportService $profitLossReportService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = $this->profitLossReportService->build($user, [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'client_id' => (string) $request->query('client_id', ''),
        ]);

        return $this->success($report, 'Profit and loss report generated successfully');
    }
}
