<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VatDeclarationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VatDeclarationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VatDeclarationService $vatDeclarationService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'period_type' => 'sometimes|in:monthly,quarterly',
        ]);

        /** @var User $user */
        $user = $request->user();

        $report = $this->vatDeclarationService->build($user, [
            'year' => (int) $request->year,
            'period_type' => $request->period_type ?? 'monthly',
        ]);

        return $this->success($report, 'VAT declaration retrieved successfully');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'period_type' => 'sometimes|in:monthly,quarterly',
        ]);

        /** @var User $user */
        $user = $request->user();

        $report = $this->vatDeclarationService->build($user, [
            'year' => (int) $request->year,
            'period_type' => $request->period_type ?? 'monthly',
        ]);

        $csv = $this->vatDeclarationService->toCsv($report);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"vat_declaration_{$request->year}.csv\"",
        ];

        return response()->stream(function () use ($csv): void {
            echo $csv;
        }, 200, $headers);
    }
}
