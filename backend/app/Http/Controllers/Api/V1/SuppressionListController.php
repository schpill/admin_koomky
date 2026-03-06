<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuppressedEmail;
use App\Models\User;
use App\Services\SuppressionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SuppressionListController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SuppressionService $suppressionService) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', SuppressedEmail::class);

        /** @var User $user */
        $user = $request->user();

        $query = SuppressedEmail::query()
            ->forUser($user)
            ->orderByDesc('suppressed_at');

        if ($request->filled('search') && is_string($request->input('search'))) {
            $query->where('email', 'like', '%'.$request->input('search').'%');
        }

        $entries = $query->paginate((int) $request->input('per_page', 15));

        return $this->success([
            'data' => $entries->items(),
            'current_page' => $entries->currentPage(),
            'per_page' => $entries->perPage(),
            'total' => $entries->total(),
            'last_page' => $entries->lastPage(),
        ], 'Suppression list retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', SuppressedEmail::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'reason' => ['required', Rule::in(['manual', 'unsubscribed', 'hard_bounce'])],
            'source_campaign_id' => ['nullable', 'uuid'],
        ]);

        $this->suppressionService->suppress(
            $user,
            (string) $validated['email'],
            (string) $validated['reason'],
            $validated['source_campaign_id'] ?? null
        );

        $entry = SuppressedEmail::query()
            ->forUser($user)
            ->where('email', mb_strtolower((string) $validated['email']))
            ->firstOrFail();

        return $this->success($entry, 'Suppression entry created successfully', Response::HTTP_CREATED);
    }

    public function destroy(SuppressedEmail $entry): JsonResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        return $this->success(null, 'Suppression entry deleted successfully');
    }

    public function import(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $result = $this->suppressionService->importCsv(
            $user,
            (string) $validated['file']->getRealPath()
        );

        return $this->success($result, 'Suppression list imported successfully');
    }

    public function export(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return $this->suppressionService->exportCsv($user);
    }
}
