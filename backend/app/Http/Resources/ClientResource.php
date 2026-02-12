<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Client $resource
 */
final class ClientResource extends JsonResource
{
    /**
     * Transform resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'type' => 'client',
            'id' => $this->id,
            'attributes' => [
                'reference' => $this->reference,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
                'vat_number' => $this->vat_number,
                'website' => $this->website,
                'billing_address' => $this->billing_address,
                'notes' => $this->notes,
                'status' => $this->status,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'tags' => TagResource::collection($this->whenLoaded('tags')),
                'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
                'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            ],
        ];
    }
}
