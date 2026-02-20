<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\WebhookDispatchService;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        // Dispatch webhook
        $this->dispatchWebhook($lead, 'lead.created');
    }

    public function updated(Lead $lead): void
    {
        // Check if status changed
        if ($lead->wasChanged('status')) {
            $previousStatus = (string) $lead->getOriginal('status');
            $newStatus = (string) $lead->status;

            // Dispatch status change webhook
            $this->dispatchWebhook($lead, 'lead.status_changed', [
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]);

            // Dispatch converted webhook if lead was converted
            if ($newStatus === 'won' && $lead->won_client_id !== null) {
                $this->dispatchWebhook($lead, 'lead.converted', [
                    'client_id' => $lead->won_client_id,
                    'converted_at' => $lead->converted_at?->toIso8601String(),
                ]);
            }
        }
    }

    /**
     * Dispatch a webhook for the lead event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Lead $lead, string $event, array $extraData = []): void
    {
        $userId = $lead->user_id;

        $data = array_merge([
            'id' => $lead->id,
            'company_name' => $lead->company_name,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => $lead->source,
            'status' => $lead->status,
            'estimated_value' => $lead->estimated_value !== null ? (float) $lead->estimated_value : null,
            'currency' => $lead->currency,
            'probability' => $lead->probability,
            'expected_close_date' => $lead->expected_close_date?->toDateString(),
            'won_client_id' => $lead->won_client_id,
            'converted_at' => $lead->converted_at?->toIso8601String(),
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }
}
