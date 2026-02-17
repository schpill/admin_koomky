<?php

namespace App\Http\Resources\Api\V1\Clients;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Client $resource
 */
class ClientResource extends JsonResource
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
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'zip_code' => $this->resource->zip_code,
            'country' => $this->resource->country,
            'preferred_currency' => $this->resource->preferred_currency,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'contacts' => $this->whenLoaded('contacts'),
            'tags' => $this->whenLoaded('tags'),
            'activities' => $this->whenLoaded('activities'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
        ];
    }
}
