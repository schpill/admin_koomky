<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Api\V1\Calendar\UpdateCalendarEventRequest;
use App\Http\Resources\Api\V1\Calendar\CalendarEventResource;
use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->inDateRange(
                $request->query('date_from') ? (string) $request->query('date_from') : null,
                $request->query('date_to') ? (string) $request->query('date_to') : null
            )
            ->byType($request->query('type') ? (string) $request->query('type') : null)
            ->orderBy('start_at')
            ->get();

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = CalendarEventResource::collection($events)->response()->getData(true);

        return $this->success($collectionPayload['data'] ?? [], 'Calendar events retrieved successfully');
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $request->validated();
        if (isset($payload['calendar_connection_id']) && ! $this->connectionOwnedByUser($user, (string) $payload['calendar_connection_id'])) {
            return $this->error('Forbidden', 403);
        }

        $payload['user_id'] = $user->id;
        $event = CalendarEvent::query()->create($payload);

        return $this->success(new CalendarEventResource($event), 'Calendar event created successfully', 201);
    }

    public function show(Request $request, CalendarEvent $calendar_event): JsonResponse
    {
        if ($calendar_event->user_id !== $request->user()?->id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(new CalendarEventResource($calendar_event), 'Calendar event retrieved successfully');
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendar_event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($calendar_event->user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        $payload = $request->validated();
        if (isset($payload['calendar_connection_id']) && ! $this->connectionOwnedByUser($user, (string) $payload['calendar_connection_id'])) {
            return $this->error('Forbidden', 403);
        }

        $calendar_event->update($payload);

        return $this->success(new CalendarEventResource($calendar_event->fresh()), 'Calendar event updated successfully');
    }

    public function destroy(Request $request, CalendarEvent $calendar_event): JsonResponse
    {
        if ($calendar_event->user_id !== $request->user()?->id) {
            return $this->error('Forbidden', 403);
        }

        $calendar_event->delete();

        return $this->success(null, 'Calendar event deleted successfully');
    }

    private function connectionOwnedByUser(User $user, string $connectionId): bool
    {
        return CalendarConnection::query()
            ->where('id', $connectionId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
