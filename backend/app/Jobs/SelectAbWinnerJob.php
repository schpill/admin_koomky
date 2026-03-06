<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SelectAbWinnerJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $campaign = Campaign::query()->with('variants')->find($this->campaignId);

        if (! $campaign || ! $campaign->isAbTest() || $campaign->ab_winner_variant_id !== null) {
            return;
        }

        /** @var \Illuminate\Support\Collection<int, CampaignVariant> $variants */
        $variants = $campaign->variants;
        if ($variants->count() < 2) {
            return;
        }

        $criteria = (string) ($campaign->ab_winner_criteria ?? 'open_rate');

        $winner = $variants->sortByDesc(function (CampaignVariant $variant) use ($criteria): float {
            if ($criteria === 'click_rate') {
                return $variant->clickRate();
            }

            return $variant->openRate();
        })->first();

        if (! $winner instanceof CampaignVariant) {
            return;
        }

        $campaign->update([
            'ab_winner_variant_id' => $winner->id,
            'ab_winner_selected_at' => now(),
        ]);
    }
}
