<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImportSessions\StoreImportSessionRequest;
use App\Jobs\ProcessProspectImportJob;
use App\Models\ImportSession;
use App\Models\User;
use App\Services\ColumnDetectorService;
use App\Services\FileParserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ImportSessionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FileParserService $fileParserService,
        private readonly ColumnDetectorService $columnDetectorService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ImportSession::class);

        /** @var User $user */
        $user = $request->user();

        $sessions = ImportSession::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return $this->success($sessions->toArray(), 'Import sessions retrieved successfully');
    }

    public function store(StoreImportSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $uploaded = $request->file('file');
        if (! $uploaded instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid file upload', 422);
        }

        $storedPath = $uploaded->storeAs('imports', uniqid('prospects_', true).'.'.$uploaded->getClientOriginalExtension(), 'local');
        if (! is_string($storedPath)) {
            return $this->error('Unable to store import file', 500);
        }
        $fullPath = Storage::disk('local')->path($storedPath);
        $extension = strtolower((string) $uploaded->getClientOriginalExtension());

        $parsed = $this->fileParserService->parse($fullPath, $extension);
        $detectedMapping = $this->columnDetectorService->detect($parsed['headers']);

        $session = ImportSession::query()->create([
            'user_id' => $user->id,
            'filename' => $storedPath,
            'original_filename' => $uploaded->getClientOriginalName(),
            'status' => 'pending',
            'total_rows' => count($parsed['rows']),
        ]);

        return $this->success([
            'session' => $session,
            'column_list' => $parsed['headers'],
            'preview_rows' => array_slice($parsed['rows'], 0, 5),
            'detected_mapping' => $detectedMapping,
        ], 'Import session created successfully', 201);
    }

    public function show(ImportSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        return $this->success([
            ...$session->toArray(),
            'progress_percent' => $session->progressPercent(),
        ], 'Import session retrieved successfully');
    }

    public function update(Request $request, ImportSession $session): JsonResponse
    {
        Gate::authorize('update', $session);

        $payload = $request->validate([
            'column_mapping' => ['required', 'array'],
            'default_tags' => ['nullable', 'array'],
            'default_tags.*' => ['string', 'max:50'],
            'options' => ['nullable', 'array'],
            'options.duplicate_strategy' => ['nullable', 'string', 'in:skip,update'],
            'options.default_status' => ['nullable', 'string', 'in:prospect,lead,active'],
        ]);

        $session->update([
            'column_mapping' => $payload['column_mapping'],
            'default_tags' => $payload['default_tags'] ?? [],
            'options' => $payload['options'] ?? [],
            'status' => 'mapping',
        ]);

        return $this->success($session->fresh(), 'Import session updated successfully');
    }

    public function process(ImportSession $session): JsonResponse
    {
        Gate::authorize('update', $session);

        if ($session->status !== 'mapping') {
            return $this->error('Import session must be in mapping status before processing.', 422);
        }

        $session->update(['status' => 'processing']);

        ProcessProspectImportJob::dispatch($session->id)->onQueue('imports');

        return $this->success($session->fresh(), 'Import processing started', 202);
    }

    public function errors(ImportSession $session, Request $request): JsonResponse
    {
        Gate::authorize('view', $session);

        $errors = $session->errors()->latest('row_number')->paginate((int) $request->input('per_page', 15));

        return $this->success($errors->toArray(), 'Import session errors retrieved successfully');
    }

    public function exportErrors(ImportSession $session): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        Gate::authorize('view', $session);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import-errors-'.$session->id.'.csv"',
        ];

        return response()->stream(function () use ($session) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['row_number', 'raw_data', 'error_message']);

            foreach ($session->errors()->orderBy('row_number')->cursor() as $error) {
                fputcsv($handle, [
                    $error->row_number,
                    json_encode($error->raw_data, JSON_UNESCAPED_UNICODE),
                    $error->error_message,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function destroy(ImportSession $session): JsonResponse
    {
        Gate::authorize('delete', $session);

        if ($session->status === 'processing') {
            return $this->error('Cannot delete a processing import session.', 422);
        }

        Storage::disk('local')->delete($session->filename);
        $session->delete();

        return $this->success(null, 'Import session deleted successfully', 204);
    }
}
