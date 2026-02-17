<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CampaignAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function forCampaign(Campaign $campaign): array
    {
        /** @var Collection<int, CampaignRecipient> $recipients */
        $recipients = $campaign->recipients()->get();

        $totalRecipients = $recipients->count();
        $sentCount = $recipients->whereIn('status', ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed'])->count();
        $deliveredCount = $recipients->filter(fn (CampaignRecipient $recipient): bool => $recipient->delivered_at !== null || in_array($recipient->status, ['delivered', 'opened', 'clicked'], true))->count();
        $openedCount = $recipients->filter(fn (CampaignRecipient $recipient): bool => $recipient->opened_at !== null || in_array($recipient->status, ['opened', 'clicked'], true))->count();
        $clickedCount = $recipients->filter(fn (CampaignRecipient $recipient): bool => $recipient->clicked_at !== null || $recipient->status === 'clicked')->count();
        $bouncedCount = $recipients->where('status', 'bounced')->count();
        $unsubscribedCount = $recipients->where('status', 'unsubscribed')->count();
        $failedCount = $recipients->where('status', 'failed')->count();

        $openRate = $deliveredCount > 0 ? round(($openedCount / $deliveredCount) * 100, 2) : 0.0;
        $clickRate = $deliveredCount > 0 ? round(($clickedCount / $deliveredCount) * 100, 2) : 0.0;
        $bounceRate = $sentCount > 0 ? round(($bouncedCount / $sentCount) * 100, 2) : 0.0;

        return [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'campaign_status' => $campaign->status,
            'total_recipients' => $totalRecipients,
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'opened_count' => $openedCount,
            'clicked_count' => $clickedCount,
            'bounced_count' => $bouncedCount,
            'unsubscribed_count' => $unsubscribedCount,
            'failed_count' => $failedCount,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'bounce_rate' => $bounceRate,
            'failure_reasons' => $this->failureReasons($recipients),
            'time_series' => $this->timeSeries($recipients),
        ];
    }

    /**
     * @param  list<string>  $campaignIds
     * @return array<int, array<string, mixed>>
     */
    public function compare(User $user, array $campaignIds): array
    {
        return Campaign::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $campaignIds)
            ->get()
            ->map(fn (Campaign $campaign): array => $this->forCampaign($campaign))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, CampaignRecipient>  $recipients
     * @return array<int, array{hour:string, opens:int, clicks:int}>
     */
    private function timeSeries(Collection $recipients): array
    {
        $buckets = [];

        /** @var CampaignRecipient $recipient */
        foreach ($recipients as $recipient) {
            $openedHour = $this->toHourBucket($recipient->opened_at);
            if ($openedHour !== null) {
                $hour = $openedHour;
                if (! isset($buckets[$hour])) {
                    $buckets[$hour] = ['hour' => $hour, 'opens' => 0, 'clicks' => 0];
                }
                $buckets[$hour]['opens']++;
            }

            $clickedHour = $this->toHourBucket($recipient->clicked_at);
            if ($clickedHour !== null) {
                $hour = $clickedHour;
                if (! isset($buckets[$hour])) {
                    $buckets[$hour] = ['hour' => $hour, 'opens' => 0, 'clicks' => 0];
                }
                $buckets[$hour]['clicks']++;
            }
        }

        ksort($buckets);

        return array_values($buckets);
    }

    /**
     * @param  Collection<int, CampaignRecipient>  $recipients
     * @return array<int, array{reason:string, count:int}>
     */
    private function failureReasons(Collection $recipients): array
    {
        /** @var array<string, int> $counts */
        $counts = $recipients
            ->filter(fn (CampaignRecipient $recipient): bool => is_string($recipient->failure_reason) && $recipient->failure_reason !== '')
            ->countBy(fn (CampaignRecipient $recipient): string => (string) $recipient->failure_reason)
            ->all();

        $results = [];

        foreach ($counts as $reason => $count) {
            $results[] = [
                'reason' => $reason,
                'count' => (int) $count,
            ];
        }

        return $results;
    }

    private function toHourBucket(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy()->startOfHour()->toDateTimeString();
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->startOfHour()->toDateTimeString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
