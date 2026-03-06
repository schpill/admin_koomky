<?php

namespace App\Jobs;

use App\Models\ContactScoreEvent;
use App\Services\ContactScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateExpiredScoresJob implements ShouldQueue
{
    use Queueable;

    public function handle(ContactScoreService $contactScoreService): void
    {
        $contactIds = ContactScoreEvent::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->distinct()
            ->pluck('contact_id');

        if ($contactIds->isEmpty()) {
            return;
        }

        \App\Models\Contact::query()
            ->whereIn('id', $contactIds)
            ->each(fn ($contact) => $contactScoreService->recalculate($contact));
    }
}
