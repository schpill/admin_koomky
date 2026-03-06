<?php

namespace App\Observers;

use App\Models\CampaignRecipient;
use App\Services\SuppressionService;

class CampaignRecipientObserver
{
    public function updated(CampaignRecipient $recipient): void
    {
        if ($recipient->status !== 'bounced' || $recipient->bounce_type !== 'hard') {
            return;
        }

        if (! $recipient->wasChanged(['status', 'bounce_type'])) {
            return;
        }

        $campaign = $recipient->campaign()->with('user')->first();
        if ($campaign === null || $campaign->user === null || ! is_string($recipient->email) || $recipient->email === '') {
            return;
        }

        app(SuppressionService::class)->suppress(
            $campaign->user,
            $recipient->email,
            'hard_bounce',
            $campaign->id
        );
    }
}
