<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Notifications\CampaignCompletedNotification;
use App\Services\SegmentFilterEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCampaignJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignId) {}

    public function handle(SegmentFilterEngine $segmentFilterEngine): void
    {
        $campaign = Campaign::query()->with(['user', 'segment'])->find($this->campaignId);

        if (! $campaign || $campaign->type !== 'email') {
            return;
        }

        $user = $campaign->user;
        if ($user === null) {
            return;
        }

        $campaign->update([
            'status' => 'sending',
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $contactsQuery = $campaign->segment
            ? $segmentFilterEngine->apply($user, (array) $campaign->segment->filters)
            : Contact::query()->whereHas('client', function ($query) use ($campaign): void {
                $query->where('user_id', $campaign->user_id);
            });

        $contacts = $contactsQuery
            ->emailSubscribed()
            ->whereNotNull('email')
            ->orderBy('contacts.id')
            ->cursor();

        $throttleRate = $this->resolveThrottleRate(
            $campaign->settings['throttle_rate_per_minute'] ?? null,
            100
        );
        $interval = 60 / $throttleRate;
        $index = 0;

        foreach ($contacts as $contact) {
            /** @var CampaignRecipient $recipient */
            $recipient = CampaignRecipient::query()->create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'status' => 'pending',
            ]);

            $delaySeconds = (int) floor($index * $interval);

            SendCampaignEmailJob::dispatch($recipient->id)
                ->delay(now()->addSeconds($delaySeconds));

            Activity::query()->create([
                'user_id' => $campaign->user_id,
                'subject_id' => $contact->client_id,
                'subject_type' => Client::class,
                'description' => 'Campaign '.$campaign->name.' sent to contact',
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_type' => $campaign->type,
                    'contact_id' => $contact->id,
                    'channel' => 'email',
                ],
            ]);

            $index++;
        }

        $campaign->update([
            'status' => 'sent',
            'completed_at' => now(),
        ]);

        $freshCampaign = $campaign->fresh();
        if ($freshCampaign instanceof Campaign) {
            $user->notify(new CampaignCompletedNotification($freshCampaign));
        }
    }

    private function resolveThrottleRate(mixed $configuredRate, int $defaultRate): int
    {
        if (is_numeric($configuredRate)) {
            $rate = (int) $configuredRate;

            return $rate > 0 ? $rate : $defaultRate;
        }

        return $defaultRate;
    }
}
