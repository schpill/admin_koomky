<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExpenseReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ExpenseReportService $expenseReportService
    ) {}

    public function report(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = $this->expenseReportService->build($user, [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'expense_category_id' => (string) $request->query('expense_category_id', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'client_id' => (string) $request->query('client_id', ''),
            'billable' => $request->query('billable'),
            'status' => (string) $request->query('status', ''),
        ]);

        return $this->success($report, 'Expense report generated successfully');
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = $this->expenseReportService->build($user, [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'expense_category_id' => (string) $request->query('expense_category_id', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'client_id' => (string) $request->query('client_id', ''),
            'billable' => $request->query('billable'),
            'status' => (string) $request->query('status', ''),
        ]);

        /** @var array<int, array<string, mixed>> $items */
        $items = $report['items'] ?? [];

        return response()->streamDownload(function () use ($items): void {
            $stream = fopen('php://output', 'w');
            if (! $stream) {
                return;
            }

            fputcsv($stream, [
                'date',
                'description',
                'category',
                'project_reference',
                'client_name',
                'amount',
                'currency',
                'tax_amount',
                'is_billable',
                'status',
            ]);

            foreach ($items as $item) {
                fputcsv($stream, [
                    $item['date'] ?? '',
                    $item['description'] ?? '',
                    $item['category'] ?? '',
                    $item['project_reference'] ?? '',
                    $item['client_name'] ?? '',
                    $item['amount'] ?? '',
                    $item['currency'] ?? '',
                    $item['tax_amount'] ?? '',
                    ($item['is_billable'] ?? false) ? '1' : '0',
                    $item['status'] ?? '',
                ]);
            }

            fclose($stream);
        }, 'expenses-report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
