<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'activity',
            'id' => $this->id,
            'attributes' => [
                'action' => $this->action,
                'description' => $this->description,
                'changes' => $this->changes,
                'created_at' => $this->created_at->toIso8601String(),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => $this->user_id,
                        'name' => $this->user?->name,
                    ],
                ],
            ],
        ];
    }
}
