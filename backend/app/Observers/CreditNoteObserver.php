<?php

namespace App\Observers;

use App\Models\CreditNote;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;

class CreditNoteObserver
{
    public function created(CreditNote $creditNote): void
    {
        $client = $creditNote->client;
        if ($client) {
            ActivityService::log($client, "Credit note created: {$creditNote->number}", [
                'credit_note_id' => $creditNote->id,
                'credit_note_number' => $creditNote->number,
                'status' => $creditNote->status,
            ]);
        }

        $this->syncCounterFromNumber($creditNote->number);
    }

    public function updated(CreditNote $creditNote): void
    {
        if (! $creditNote->wasChanged('status')) {
            return;
        }

        $client = $creditNote->client;
        if (! $client) {
            return;
        }

        $previousStatus = (string) $creditNote->getOriginal('status');
        $newStatus = (string) $creditNote->status;

        ActivityService::log($client, "Credit note status changed: {$creditNote->number}", [
            'credit_note_id' => $creditNote->id,
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);
    }

    private function syncCounterFromNumber(string $number): void
    {
        if (! preg_match('/^([A-Z]+)-(\d{4})-(\d{4})$/', $number, $matches)) {
            return;
        }

        $prefix = $matches[1];
        $year = $matches[2];
        $value = (int) $matches[3];
        $counterKey = "credit_notes:{$prefix}:{$year}";

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
