<?php

namespace App\Http\Resources\Api\V1\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\CalendarEvent $resource
 */
class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'calendar_connection_id' => $this->resource->calendar_connection_id,
            'external_id' => $this->resource->external_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'start_at' => $this->resource->start_at->toDateTimeString(),
            'end_at' => $this->resource->end_at->toDateTimeString(),
            'all_day' => $this->resource->all_day,
            'location' => $this->resource->location,
            'type' => $this->resource->type,
            'eventable_type' => $this->resource->eventable_type,
            'eventable_id' => $this->resource->eventable_id,
            'recurrence_rule' => $this->resource->recurrence_rule,
            'sync_status' => $this->resource->sync_status,
            'external_updated_at' => $this->resource->external_updated_at?->toDateTimeString(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
