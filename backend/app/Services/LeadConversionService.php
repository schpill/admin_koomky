<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\DB;

/**
 * Service for converting leads to clients.
 */
class LeadConversionService
{
    /**
     * Convert a won lead to a client.
     *
     * @param  array<string, mixed>  $overrides  Fields to override in client creation
     */
    public function convert(Lead $lead, array $overrides = []): Client
    {
        if (! $lead->canConvert()) {
            throw new \RuntimeException('Lead cannot be converted: must be won status and not already converted');
        }

        return DB::transaction(function () use ($lead, $overrides): Client {
            // Check for existing client with same email
            $existingClient = null;
            if ($lead->email) {
                $existingClient = Client::query()
                    ->where('user_id', $lead->user_id)
                    ->where('email', $lead->email)
                    ->first();
            }

            if ($existingClient) {
                // Link existing client
                $lead->won_client_id = $existingClient->id;
                $lead->converted_at = now();
                $lead->save();

                // Log activity
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'note',
                    'content' => 'Converted to existing client: '.$existingClient->name,
                ]);

                return $existingClient;
            }

            // Create new client from lead data
            $client = Client::create(array_merge([
                'user_id' => $lead->user_id,
                'reference' => $this->generateReference(),
                'name' => trim(($lead->company_name ?? '').' '.$lead->first_name.' '.$lead->last_name),
                'email' => $lead->email,
                'phone' => $lead->phone,
            ], $overrides));

            // Update lead
            $lead->won_client_id = $client->id;
            $lead->converted_at = now();
            $lead->save();

            // Log activity
            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'note',
                'content' => 'Converted to new client: '.$client->name,
            ]);

            return $client;
        });
    }

    /**
     * Generate a unique client reference.
     */
    private function generateReference(): string
    {
        $year = now()->year;
        $prefix = "CLI-{$year}-";

        // Get the last reference number for this year
        $lastClient = Client::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderBy('reference', 'desc')
            ->first();

        if ($lastClient && preg_match('/CLI-\d{4}-(\d{4})$/', $lastClient->reference, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1000;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
