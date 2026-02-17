<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Segments\StoreSegmentRequest;
use App\Models\Segment;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class SegmentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SegmentFilterEngine $segmentFilterEngine) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $segments = Segment::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        $segmentData = collect($segments->items())->map(function (Segment $segment): array {
            $payload = $segment->toArray();
            $payload['cached_contact_count'] = (int) Cache::get(
                $this->segmentContactCountCacheKey($segment->id),
                (int) $segment->contact_count
            );

            return $payload;
        })->values()->all();

        return $this->success([
            'data' => $segmentData,
            'current_page' => $segments->currentPage(),
            'per_page' => $segments->perPage(),
            'total' => $segments->total(),
            'last_page' => $segments->lastPage(),
        ], 'Segments retrieved successfully');
    }

    public function store(StoreSegmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Segment::class);

        /** @var User $user */
        $user = $request->user();

        $payload = $request->validated();
        $payload['user_id'] = $user->id;
        $payload['is_dynamic'] = $payload['is_dynamic'] ?? true;

        $segment = Segment::query()->create($payload);

        $this->forgetSegmentCache($segment->id);

        return $this->success($segment, 'Segment created successfully', 201);
    }

    public function show(Segment $segment): JsonResponse
    {
        Gate::authorize('view', $segment);

        return $this->success($segment, 'Segment retrieved successfully');
    }

    public function update(StoreSegmentRequest $request, Segment $segment): JsonResponse
    {
        Gate::authorize('update', $segment);

        $segment->update($request->validated());
        $this->forgetSegmentCache($segment->id);

        return $this->success($segment, 'Segment updated successfully');
    }

    public function destroy(Segment $segment): JsonResponse
    {
        Gate::authorize('delete', $segment);

        $this->forgetSegmentCache($segment->id);
        $segment->delete();

        return $this->success(null, 'Segment deleted successfully');
    }

    public function preview(Request $request, Segment $segment): JsonResponse
    {
        Gate::authorize('view', $segment);

        /** @var User $user */
        $user = $request->user();

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));

        $query = $this->segmentFilterEngine
            ->apply($user, $segment->filters)
            ->emailSubscribed()
            ->with(['client:id,name']);

        $contacts = $query->paginate($perPage);
        $totalMatching = $contacts->total();

        Cache::put($this->segmentContactCountCacheKey($segment->id), $totalMatching, now()->addMinutes(5));

        if ($segment->contact_count !== $totalMatching) {
            $segment->forceFill(['contact_count' => $totalMatching])->save();
        }

        return $this->success([
            'segment_id' => $segment->id,
            'total_matching' => $totalMatching,
            'cached_contact_count' => (int) Cache::get($this->segmentContactCountCacheKey($segment->id), $totalMatching),
            'contacts' => $contacts->toArray(),
        ], 'Segment preview generated successfully');
    }

    private function forgetSegmentCache(string $segmentId): void
    {
        Cache::forget($this->segmentContactCountCacheKey($segmentId));
    }

    private function segmentContactCountCacheKey(string $segmentId): string
    {
        return 'segments:'.$segmentId.':contact_count';
    }
}
