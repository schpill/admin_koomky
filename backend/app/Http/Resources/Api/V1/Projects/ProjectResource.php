<?php

namespace App\Http\Resources\Api\V1\Projects;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Project $resource
 */
class ProjectResource extends JsonResource
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
            'reference' => $this->resource->reference,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'billing_type' => $this->resource->billing_type,
            'hourly_rate' => $this->resource->hourly_rate,
            'fixed_price' => $this->resource->fixed_price,
            'estimated_hours' => $this->resource->estimated_hours,
            'start_date' => $this->resource->start_date?->toDateString(),
            'deadline' => $this->resource->deadline?->toDateString(),
            'completed_at' => $this->resource->completed_at,
            'client' => $this->whenLoaded('client'),
            'total_tasks' => $this->resource->total_tasks,
            'completed_tasks' => $this->resource->completed_tasks,
            'total_time_spent' => $this->resource->total_time_spent,
            'progress_percentage' => $this->resource->progress_percentage,
            'budget_consumed' => $this->resource->budget_consumed,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
