<?php

namespace App\Http\Resources\Api\V1\Quotes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Quote $resource
 */
class QuoteResource extends JsonResource
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
            'project_id' => $this->resource->project_id,
            'converted_invoice_id' => $this->resource->converted_invoice_id,
            'number' => $this->resource->number,
            'status' => $this->resource->status,
            'issue_date' => $this->resource->issue_date->toDateString(),
            'valid_until' => $this->resource->valid_until->toDateString(),
            'subtotal' => (float) $this->resource->subtotal,
            'tax_amount' => (float) $this->resource->tax_amount,
            'discount_type' => $this->resource->discount_type,
            'discount_value' => $this->resource->discount_value !== null ? (float) $this->resource->discount_value : null,
            'discount_amount' => (float) $this->resource->discount_amount,
            'total' => (float) $this->resource->total,
            'currency' => $this->resource->currency,
            'base_currency' => $this->resource->base_currency,
            'exchange_rate' => $this->resource->exchange_rate !== null ? (float) $this->resource->exchange_rate : null,
            'base_currency_total' => $this->resource->base_currency_total !== null ? (float) $this->resource->base_currency_total : null,
            'notes' => $this->resource->notes,
            'pdf_path' => $this->resource->pdf_path,
            'sent_at' => $this->resource->sent_at,
            'accepted_at' => $this->resource->accepted_at,
            'client' => $this->whenLoaded('client'),
            'project' => $this->whenLoaded('project'),
            'converted_invoice' => $this->whenLoaded('convertedInvoice'),
            'line_items' => $this->whenLoaded('lineItems'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
