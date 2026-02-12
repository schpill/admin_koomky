<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Resources\Json\JsonResource;

final class ContactResource extends JsonResource
{
    /**
     * Transform resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Contact $contact */
        $contact = $this->resource;

        return [
            'type' => 'contact',
            'id' => $contact->id,
            'attributes' => [
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'position' => $contact->position,
                'is_primary' => $contact->is_primary,
                'created_at' => $contact->created_at?->toIso8601String(),
                'updated_at' => $contact->updated_at?->toIso8601String(),
            ],
        ];
    }
}
