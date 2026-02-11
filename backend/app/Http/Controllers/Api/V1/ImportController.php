<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private ExportService $exportService
    ) {
    }

    /**
     * Get import template.
     */
    public function template(): JsonResponse
    {
        $csv = $this->importService->generateCsvTemplate();

        return response()->json([
            'data' => [
                'type' => 'import_template',
                'attributes' => [
                    'content' => base64_encode($csv),
                    'filename' => 'clients_template.csv',
                ],
            ],
        ]);
    }

    /**
     * Import clients from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB
        ]);

        $file = $request->file('file');
        $user = Auth::user();

        // Store file temporarily
        $filePath = $file->storeAs('imports', 'import_' . now()->format('YmdHis') . '.csv');

        $result = $this->importService->importClientsFromCsv($user, storage_path('app/' . $filePath));

        return response()->json([
            'data' => [
                'type' => 'import_result',
                'attributes' => $result,
            ],
            'meta' => [
                'message' => 'Import completed successfully.',
            ],
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Export clients to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $user = Auth::user();
        $query = $user->clients();

        // Export specific IDs or all
        if ($request->has('ids')) {
            $query->whereIn('id', $request->input('ids'));
        }

        $clients = $query->get();
        $fileName = 'clients_export_' . now()->format('YmdHis');
        $filePath = $this->exportService->exportClientsToCsv($clients, $fileName);

        return response()->json([
            'data' => [
                'type' => 'export',
                'attributes' => [
                    'filename' => $fileName . '.csv',
                    'size' => filesize($filePath),
                ],
            ],
        ]);
    }
}
