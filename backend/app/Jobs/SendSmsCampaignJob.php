<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Notifications\CampaignCompletedNotification;
use App\Services\PhoneValidationService;
use App\Services\SegmentFilterEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsCampaignJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignId) {}

    public function handle(SegmentFilterEngine $segmentFilterEngine, PhoneValidationService $phoneValidationService): void
    {
        $campaign = Campaign::query()->with(['user', 'segment'])->find($this->campaignId);

        if (! $campaign || $campaign->type !== 'sms') {
            return;
        }

        $user = $campaign->user;
        if ($user === null) {
            return;
        }

        $contactsQuery = $campaign->segment
            ? $segmentFilterEngine->apply($user, (array) $campaign->segment->filters)
            : Contact::query()->whereHas('client', function ($query) use ($campaign): void {
                $query->where('user_id', $campaign->user_id);
            });

        $contacts = $contactsQuery
            ->smsOptedIn()
            ->whereNotNull('phone')
            ->get()
            ->filter(fn (Contact $contact): bool => is_string($contact->phone) && $phoneValidationService->isValidE164($contact->phone))
            ->values();

        $throttleRate = (int) (($campaign->settings['throttle_rate_per_minute'] ?? null) ?: 30);
        $throttleRate = max(1, $throttleRate);
        $interval = 60 / $throttleRate;

        foreach ($contacts as $index => $contact) {
            /** @var CampaignRecipient $recipient */
            $recipient = CampaignRecipient::query()->create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'status' => 'pending',
            ]);

            $delaySeconds = (int) floor($index * $interval);

            SendCampaignSmsJob::dispatch($recipient->id)
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
                    'channel' => 'sms',
                ],
            ]);
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
}
