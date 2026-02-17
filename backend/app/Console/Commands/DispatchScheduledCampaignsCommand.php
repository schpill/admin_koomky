<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailCampaignJob;
use App\Jobs\SendSmsCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class DispatchScheduledCampaignsCommand extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled';

    protected $description = 'Dispatch scheduled campaigns ready to send';

    public function handle(): int
    {
        $campaigns = Campaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update([
                'status' => 'sending',
                'started_at' => $campaign->started_at ?? now(),
            ]);

            if ($campaign->type === 'sms') {
                SendSmsCampaignJob::dispatch($campaign->id);
            } else {
                SendEmailCampaignJob::dispatch($campaign->id);
            }
        }

        return self::SUCCESS;
    }
}
