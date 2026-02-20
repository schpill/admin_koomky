<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FiscalYearSummaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalYearSummaryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'sometimes|integer|min:2000|max:2100',
        ]);

        /** @var User $user */
        $user = $request->user();

        $year = (int) ($request->year ?? now()->year);

        $service = new FiscalYearSummaryService;
        $summary = $service->build($user, ['year' => $year]);

        return $this->success($summary, 'Fiscal year summary retrieved successfully');
    }
}
