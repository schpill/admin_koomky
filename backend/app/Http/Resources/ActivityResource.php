<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Resources\Json\JsonResource;

final class ActivityResource extends JsonResource
{
    /**
     * Transform resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Activity $activity */
        $activity = $this->resource;

        return [
            'type' => 'activity',
            'id' => $activity->id,
            'attributes' => [
                'action' => $activity->type,
                'description' => $activity->description,
                'changes' => $activity->metadata,
                'created_at' => $activity->created_at->toIso8601String(),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => $activity->user_id,
                        'name' => $activity->user?->name,
                    ],
                ],
            ],
        ];
    }
}
