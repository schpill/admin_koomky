<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClientResource extends JsonResource
{
    /**
     * Transform resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Client $client */
        $client = $this->resource;

        return [
            'type' => 'client',
            'id' => $client->id,
            'attributes' => [
                'reference' => $client->reference,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'company' => $client->company,
                'vat_number' => $client->vat_number,
                'website' => $client->website,
                'billing_address' => $client->billing_address,
                'notes' => $client->notes,
                'status' => $client->status,
                'created_at' => $client->created_at?->toIso8601String(),
                'updated_at' => $client->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'tags' => TagResource::collection($this->whenLoaded('tags')),
                'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
                'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            ],
        ];
    }
}
