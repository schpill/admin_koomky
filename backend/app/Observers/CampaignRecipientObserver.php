<?php

namespace App\Observers;

use App\Models\CampaignRecipient;
use App\Services\ContactScoreService;
use App\Services\SuppressionService;
use App\Services\WebhookDispatchService;
use Carbon\CarbonInterface;

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

        if ($recipient->contact !== null) {
            app(ContactScoreService::class)->recordEvent($recipient->contact, 'email_bounced', $campaign);
        }

        $bouncedAt = $recipient->bounced_at;

        app(WebhookDispatchService::class)->dispatch('email.bounced', [
            'campaign_id' => $recipient->campaign_id,
            'contact_id' => $recipient->contact_id,
            'bounce_type' => $recipient->bounce_type,
            'bounced_at' => $bouncedAt instanceof CarbonInterface ? $bouncedAt->toIso8601String() : (is_string($bouncedAt) ? $bouncedAt : null),
        ], $campaign->user_id);
    }
}
