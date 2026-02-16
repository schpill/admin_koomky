<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OutstandingReportService;
use App\Services\RevenueReportService;
use App\Services\VatSummaryReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RevenueReportService $revenueReportService,
        protected OutstandingReportService $outstandingReportService,
        protected VatSummaryReportService $vatSummaryReportService,
    ) {}

    public function revenue(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            $this->revenueReportService->build($user, $this->extractFilters($request)),
            'Revenue report generated successfully'
        );
    }

    public function outstanding(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            $this->outstandingReportService->build($user, $this->extractFilters($request)),
            'Outstanding report generated successfully'
        );
    }

    public function vatSummary(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            $this->vatSummaryReportService->build($user, $this->extractFilters($request)),
            'VAT summary report generated successfully'
        );
    }

    public function export(Request $request): Response|StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $type = (string) $request->query('type', 'revenue');
        $format = (string) $request->query('format', 'csv');
        $filters = $this->extractFilters($request);

        $data = match ($type) {
            'outstanding' => $this->outstandingReportService->build($user, $filters),
            'vat-summary' => $this->vatSummaryReportService->build($user, $filters),
            default => $this->revenueReportService->build($user, $filters),
        };

        if ($format === 'pdf') {
            $pdf = $this->buildSimplePdf($type, $data);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="report-'.$type.'.pdf"',
            ]);
        }

        return $this->streamCsv($type, $data);
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'client_id' => (string) $request->query('client_id', ''),
            'project_id' => (string) $request->query('project_id', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function streamCsv(string $type, array $data): StreamedResponse
    {
        return response()->streamDownload(function () use ($type, $data): void {
            $output = fopen('php://output', 'w');
            if (! $output) {
                return;
            }

            if ($type === 'outstanding') {
                fputcsv($output, ['number', 'client_name', 'status', 'due_date', 'aging_days', 'balance_due']);
                foreach ($data['items'] ?? [] as $row) {
                    fputcsv($output, [
                        $row['number'] ?? '',
                        $row['client_name'] ?? '',
                        $row['status'] ?? '',
                        $row['due_date'] ?? '',
                        $row['aging_days'] ?? '',
                        $row['balance_due'] ?? '',
                    ]);
                }
            } elseif ($type === 'vat-summary') {
                fputcsv($output, ['rate', 'taxable_amount', 'vat_amount']);
                foreach ($data['by_rate'] ?? [] as $row) {
                    fputcsv($output, [
                        $row['rate'] ?? '',
                        $row['taxable_amount'] ?? '',
                        $row['vat_amount'] ?? '',
                    ]);
                }
            } else {
                fputcsv($output, ['month', 'total']);
                foreach ($data['by_month'] ?? [] as $row) {
                    fputcsv($output, [
                        $row['month'] ?? '',
                        $row['total'] ?? '',
                    ]);
                }
            }

            fclose($output);
        }, 'report-'.$type.'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildSimplePdf(string $type, array $data): string
    {
        $text = 'Report '.strtoupper($type).' ';
        if ($type === 'outstanding') {
            $text .= 'Total outstanding: '.($data['total_outstanding'] ?? 0);
        } elseif ($type === 'vat-summary') {
            $text .= 'Total VAT: '.($data['total_vat'] ?? 0);
        } else {
            $text .= 'Total revenue: '.($data['total_revenue'] ?? 0);
        }

        $safeText = substr((string) $text, 0, 180);
        $safeText = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safeText);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';

        $stream = "BT /F1 12 Tf 40 800 Td ({$safeText}) Tj ET";
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n";
        $pdf .= '0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }

        $pdf .= 'trailer'."\n";
        $pdf .= '<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= 'startxref'."\n";
        $pdf .= $xrefOffset."\n";
        $pdf .= '%%EOF';

        return $pdf;
    }
}
