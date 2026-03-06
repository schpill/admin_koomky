<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetryBouncedEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $recipientId)
    {
        $this->onQueue('campaigns');
    }

    public function handle(): void
    {
        $recipient = CampaignRecipient::query()
            ->with('campaign')
            ->find($this->recipientId);

        if (! $recipient || $recipient->bounce_type !== 'soft') {
            return;
        }

        if ((int) $recipient->bounce_count >= 2) {
            $recipient->update([
                'status' => 'bounced',
                'bounce_count' => max(3, (int) $recipient->bounce_count + 1),
                'bounce_type' => 'hard',
                'bounced_at' => now(),
            ]);

            return;
        }

        $recipient->update([
            'status' => 'pending',
        ]);

        SendCampaignEmailJob::dispatch($recipient->id);
    }
}
