<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

final class TagResource extends JsonResource
{
    /**
     * Transform resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Tag $tag */
        $tag = $this->resource;

        return [
            'type' => 'tag',
            'id' => $tag->id,
            'attributes' => [
                'name' => $tag->name,
                'color' => $tag->color,
                'created_at' => $tag->created_at?->toIso8601String(),
                'updated_at' => $tag->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'clients' => ClientResource::collection($this->whenLoaded('clients')),
            ],
        ];
    }
}
