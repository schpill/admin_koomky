<?php

namespace App\Observers;

use App\Models\Quote;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;

class QuoteObserver
{
    public function created(Quote $quote): void
    {
        $client = $quote->client;
        if ($client) {
            ActivityService::log($client, "Quote created: {$quote->number}", [
                'quote_id' => $quote->id,
                'quote_number' => $quote->number,
                'status' => $quote->status,
            ]);
        }

        $this->syncCounterFromNumber($quote->number);
    }

    public function updated(Quote $quote): void
    {
        if (! $quote->wasChanged('status')) {
            return;
        }

        $client = $quote->client;
        if (! $client) {
            return;
        }

        $previousStatus = (string) $quote->getOriginal('status');
        $newStatus = (string) $quote->status;

        ActivityService::log($client, "Quote status changed: {$quote->number}", [
            'quote_id' => $quote->id,
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);

        if (in_array($newStatus, ['sent', 'accepted', 'rejected', 'expired'], true)) {
            ActivityService::log($client, "Quote {$newStatus}: {$quote->number}", [
                'quote_id' => $quote->id,
                'quote_number' => $quote->number,
                'status' => $newStatus,
            ]);
        }
    }

    private function syncCounterFromNumber(string $number): void
    {
        if (! preg_match('/^([A-Z]+)-(\d{4})-(\d{4})$/', $number, $matches)) {
            return;
        }

        $prefix = $matches[1];
        $year = $matches[2];
        $value = (int) $matches[3];
        $counterKey = "quotes:{$prefix}:{$year}";

        DB::transaction(function () use ($counterKey, $value): void {
            $counter = DB::table('reference_counters')
                ->where('counter_key', $counterKey)
                ->lockForUpdate()
                ->first();

            $now = now();

            if (! $counter) {
                DB::table('reference_counters')->insert([
                    'counter_key' => $counterKey,
                    'last_number' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }

            if ((int) $counter->last_number >= $value) {
                return;
            }

            DB::table('reference_counters')
                ->where('counter_key', $counterKey)
                ->update([
                    'last_number' => $value,
                    'updated_at' => $now,
                ]);
        });
    }
}
