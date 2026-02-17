<?php

namespace App\Http\Resources\Api\V1\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\CalendarConnection $resource
 */
class CalendarConnectionResource extends JsonResource
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
            'provider' => $this->resource->provider,
            'name' => $this->resource->name,
            'calendar_id' => $this->resource->calendar_id,
            'sync_enabled' => $this->resource->sync_enabled,
            'last_synced_at' => $this->resource->last_synced_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
