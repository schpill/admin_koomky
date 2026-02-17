<?php

namespace App\Http\Resources\Api\V1\RecurringInvoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\RecurringInvoiceProfile $resource
 */
class RecurringInvoiceProfileResource extends JsonResource
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
            'name' => $this->resource->name,
            'frequency' => $this->resource->frequency,
            'start_date' => $this->resource->start_date->toDateString(),
            'end_date' => $this->resource->end_date?->toDateString(),
            'next_due_date' => $this->resource->next_due_date->toDateString(),
            'day_of_month' => $this->resource->day_of_month,
            'line_items' => $this->resource->line_items,
            'notes' => $this->resource->notes,
            'payment_terms_days' => $this->resource->payment_terms_days,
            'tax_rate' => $this->resource->tax_rate !== null ? (float) $this->resource->tax_rate : null,
            'discount_percent' => $this->resource->discount_percent !== null ? (float) $this->resource->discount_percent : null,
            'status' => $this->resource->status,
            'last_generated_at' => $this->resource->last_generated_at,
            'occurrences_generated' => $this->resource->occurrences_generated,
            'max_occurrences' => $this->resource->max_occurrences,
            'auto_send' => (bool) $this->resource->auto_send,
            'currency' => $this->resource->currency,
            'client' => $this->whenLoaded('client'),
            'generated_invoices' => $this->whenLoaded('invoices'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
