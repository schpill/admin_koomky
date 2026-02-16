<?php

namespace App\Http\Resources\Api\V1\CreditNotes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\CreditNote $resource
 */
class CreditNoteResource extends JsonResource
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
            'client_id' => $this->resource->client_id,
            'invoice_id' => $this->resource->invoice_id,
            'number' => $this->resource->number,
            'status' => $this->resource->status,
            'issue_date' => $this->resource->issue_date->toDateString(),
            'subtotal' => (float) $this->resource->subtotal,
            'tax_amount' => (float) $this->resource->tax_amount,
            'total' => (float) $this->resource->total,
            'currency' => $this->resource->currency,
            'reason' => $this->resource->reason,
            'pdf_path' => $this->resource->pdf_path,
            'sent_at' => $this->resource->sent_at,
            'applied_at' => $this->resource->applied_at,
            'client' => $this->whenLoaded('client'),
            'invoice' => $this->whenLoaded('invoice'),
            'line_items' => $this->whenLoaded('lineItems'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
