<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountingExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingExportController extends Controller
{
    use ApiResponse;

    public function formats(): JsonResponse
    {
        $formats = [
            ['value' => 'pennylane', 'label' => 'Pennylane'],
            ['value' => 'sage', 'label' => 'Sage'],
            ['value' => 'generic', 'label' => 'Generic CSV'],
        ];

        return $this->success($formats, 'Available export formats retrieved successfully');
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'format' => 'required|in:pennylane,sage,generic',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        /** @var User $user */
        $user = $request->user();

        $service = new AccountingExportService;
        $generator = $service->generate($user, $request->format, [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"accounting_export_{$request->format}_{$request->date_from}_{$request->date_to}.csv\"",
        ];

        return response()->stream(function () use ($generator): void {
            foreach ($generator as $line) {
                echo $line."\n";
            }
        }, 200, $headers);
    }
}
