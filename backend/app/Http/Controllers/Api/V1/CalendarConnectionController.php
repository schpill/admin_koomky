<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Calendar\StoreCalendarConnectionRequest;
use App\Http\Requests\Api\V1\Calendar\UpdateCalendarConnectionRequest;
use App\Http\Resources\Api\V1\Calendar\CalendarConnectionResource;
use App\Models\CalendarConnection;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CalendarConnectionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', CalendarConnection::class);

        /** @var User $user */
        $user = $request->user();

        $connections = CalendarConnection::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = CalendarConnectionResource::collection($connections)->response()->getData(true);

        return $this->success($collectionPayload['data'] ?? [], 'Calendar connections retrieved successfully');
    }

    public function store(StoreCalendarConnectionRequest $request): JsonResponse
    {
        Gate::authorize('create', CalendarConnection::class);

        /** @var User $user */
        $user = $request->user();

        $payload = $request->validated();
        $payload['user_id'] = $user->id;

        $connection = CalendarConnection::query()->create($payload);

        return $this->success(new CalendarConnectionResource($connection), 'Calendar connection created successfully', 201);
    }

    public function show(CalendarConnection $calendar_connection): JsonResponse
    {
        Gate::authorize('view', $calendar_connection);

        return $this->success(new CalendarConnectionResource($calendar_connection), 'Calendar connection retrieved successfully');
    }

    public function update(UpdateCalendarConnectionRequest $request, CalendarConnection $calendar_connection): JsonResponse
    {
        Gate::authorize('update', $calendar_connection);

        $calendar_connection->update($request->validated());

        return $this->success(new CalendarConnectionResource($calendar_connection->fresh()), 'Calendar connection updated successfully');
    }

    public function destroy(CalendarConnection $calendar_connection): JsonResponse
    {
        Gate::authorize('delete', $calendar_connection);

        $calendar_connection->delete();

        return $this->success(null, 'Calendar connection deleted successfully');
    }

    public function test(CalendarConnection $calendar_connection): JsonResponse
    {
        Gate::authorize('view', $calendar_connection);

        $credentials = $calendar_connection->credentials;
        $hasCredentials = $credentials !== [];

        return $this->success([
            'ok' => $hasCredentials,
            'provider' => $calendar_connection->provider,
        ], 'Calendar connection test completed');
    }

    public function googleCallback(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'uuid'],
        ]);

        $connection = CalendarConnection::query()
            ->where('id', $validated['state'])
            ->where('user_id', $user->id)
            ->first();

        if (! $connection) {
            return $this->error('Calendar connection not found', 404);
        }

        $credentials = (array) $connection->credentials;
        $credentials['last_oauth_code'] = $validated['code'];
        $connection->update(['credentials' => $credentials]);

        return $this->success([
            'connection_id' => $connection->id,
            'provider' => $connection->provider,
        ], 'Google calendar callback processed successfully');
    }
}
