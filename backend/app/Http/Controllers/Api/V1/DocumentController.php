<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\StoreDocumentRequest;
use App\Http\Requests\Api\V1\Documents\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\DocumentMailService;
use App\Services\DocumentStorageService;
use App\Services\DocumentTypeDetectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentTypeDetectorService $detector,
        protected DocumentStorageService $storage,
        protected DocumentMailService $mailer
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
        $query = $user->documents();

        // Filters
        if ($request->has('client_id')) {
            $query->byClient($request->client_id);
        }

        if ($request->has('document_type')) {
            $query->byType($request->document_type);
        }

        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Search via Scout if q is present
        if ($request->filled('q')) {
            $ids = Document::search($request->q)
                ->where('user_id', $user->id)
                ->keys();

            $query->whereIn('id', $ids);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        return response()->json($query->paginate($request->get('per_page', 24)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $detection = $this->detector->detect($file);
            $path = $this->storage->store($file, $user);

            $title = $request->title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $document = $user->documents()->create([
                'client_id' => $request->client_id,
                'title' => $title,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'storage_disk' => 'local',
                'mime_type' => $detection->mime_type,
                'document_type' => $detection->document_type,
                'script_language' => $detection->script_language,
                'file_size' => $file->getSize(),
                'version' => 1,
                'tags' => $request->tags ?? [],
            ]);

            return response()->json($document, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        return response()->json($document->load('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $document->update($request->validated());

        return response()->json($document);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): JsonResponse
    {
        Gate::authorize('delete', $document);

        $this->storage->delete($document->storage_path);
        $document->delete();

        return response()->json(null, 204);
    }

    /**
     * Reupload the file for an existing document.
     */
    public function reupload(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('reupload', $document);

        $request->validate([
            'file' => 'required|file|max:'.(config('performance.max_document_upload_mb', 50) * 1024),
        ]);

        $file = $request->file('file');
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $detection = $this->detector->detect($file);
            $this->storage->overwrite($document->storage_path, $file, $user);

            $document->update([
                'mime_type' => $detection->mime_type,
                'document_type' => $detection->document_type,
                'script_language' => $detection->script_language,
                'file_size' => $file->getSize(),
                'original_filename' => $file->getClientOriginalName(),
                'version' => $document->version + 1,
            ]);

            return response()->json($document);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Download the document.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        return $this->storage->streamDownload(
            $document->storage_path,
            $document->mime_type,
            $document->original_filename,
            $request->boolean('inline')
        );
    }

    /**
     * Send the document by email.
     */
    public function sendEmail(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('email', $document);

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        try {
            $this->mailer->send($document, $request->email, $request->message);

            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Bulk destroy documents.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid',
        ]);

        $documents = Document::whereIn('id', $request->ids)
            ->where('user_id', $request->user()->id)
            ->get();

        foreach ($documents as $document) {
            $this->storage->delete($document->storage_path);
            $document->delete();
        }

        return response()->json(['message' => count($documents).' documents deleted']);
    }

    /**
     * Get document storage stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $stats = $user->documents()
            ->selectRaw('document_type, count(*) as count, sum(file_size) as size')
            ->groupBy('document_type')
            ->get();

        $totalCount = $stats->sum('count');
        $totalSize = $stats->sum('size');
        $quotaBytes = $user->document_storage_quota_mb * 1024 * 1024;

        return response()->json([
            'total_count' => $totalCount,
            'total_size_bytes' => (int) $totalSize,
            'quota_bytes' => $quotaBytes,
            'usage_percentage' => $quotaBytes > 0 ? round(($totalSize / $quotaBytes) * 100, 2) : 0,
            'by_type' => $stats,
        ]);
    }
}
