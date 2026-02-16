<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Activity $resource
 */
class ActivityResource extends JsonResource
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
            'description' => $this->resource->description,
            'subject_id' => $this->resource->subject_id,
            'subject_type' => str_replace('App\\Models\\', '', (string) $this->resource->subject_type),
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at,
        ];
    }
}
