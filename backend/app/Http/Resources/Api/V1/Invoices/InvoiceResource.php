<?php

namespace App\Http\Resources\Api\V1\Invoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Invoice $resource
 */
class InvoiceResource extends JsonResource
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
            'number' => $this->resource->number,
            'status' => $this->resource->status,
            'issue_date' => $this->resource->issue_date?->toDateString(),
            'due_date' => $this->resource->due_date?->toDateString(),
            'subtotal' => (float) $this->resource->subtotal,
            'tax_amount' => (float) $this->resource->tax_amount,
            'discount_type' => $this->resource->discount_type,
            'discount_value' => $this->resource->discount_value !== null ? (float) $this->resource->discount_value : null,
            'discount_amount' => (float) $this->resource->discount_amount,
            'total' => (float) $this->resource->total,
            'currency' => $this->resource->currency,
            'notes' => $this->resource->notes,
            'payment_terms' => $this->resource->payment_terms,
            'pdf_path' => $this->resource->pdf_path,
            'sent_at' => $this->resource->sent_at,
            'viewed_at' => $this->resource->viewed_at,
            'paid_at' => $this->resource->paid_at,
            'amount_paid' => (float) $this->resource->amount_paid,
            'balance_due' => (float) $this->resource->balance_due,
            'client' => $this->whenLoaded('client'),
            'project' => $this->whenLoaded('project'),
            'line_items' => $this->whenLoaded('lineItems'),
            'payments' => $this->whenLoaded('payments'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
