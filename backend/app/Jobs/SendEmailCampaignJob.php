<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignVariant;
use App\Models\Client;
use App\Models\Contact;
use App\Models\EmailWarmupPlan;
use App\Models\SuppressedEmail;
use App\Notifications\CampaignCompletedNotification;
use App\Services\ContactScoreService;
use App\Services\ContactSendTimeService;
use App\Services\SegmentFilterEngine;
use App\Services\WarmupGuardService;
use App\Services\WebhookDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCampaignJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignId) {}

    public function handle(
        SegmentFilterEngine $segmentFilterEngine,
        ?ContactSendTimeService $contactSendTimeService = null,
        ?ContactScoreService $contactScoreService = null,
        ?WarmupGuardService $warmupGuardService = null,
        ?WebhookDispatchService $webhookDispatchService = null,
    ): void {
        $contactSendTimeService ??= app(ContactSendTimeService::class);
        $contactScoreService ??= app(ContactScoreService::class);
        $warmupGuardService ??= app(WarmupGuardService::class);
        $webhookDispatchService ??= app(WebhookDispatchService::class);

        $campaign = Campaign::query()->with(['user', 'segment', 'variants'])->find($this->campaignId);

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
            ->select('contacts.*')
            ->distinct()
            ->emailSubscribed()
            ->whereNotNull('email')
            ->orderBy('contacts.id')
            ->get()
            ->values();

        $suppressedEmails = SuppressedEmail::query()
            ->where('user_id', $user->id)
            ->pluck('email')
            ->map(fn (mixed $email): string => mb_strtolower(trim((string) $email)))
            ->filter()
            ->flip();

        $variantAssignments = $this->resolveVariantAssignments($campaign, $contacts->count());

        $throttleRate = $this->resolveThrottleRate(
            $campaign->settings['throttle_rate_per_minute'] ?? null,
            100
        );
        $interval = 60 / $throttleRate;
        $hasWarmupPlan = $warmupGuardService->activePlan($user) instanceof EmailWarmupPlan;
        $quotaReached = false;

        foreach ($contacts as $index => $contact) {
            $email = is_string($contact->email) ? trim($contact->email) : '';
            if ($email === '') {
                continue;
            }

            if ($suppressedEmails->has(mb_strtolower($email))) {
                continue;
            }

            if ($hasWarmupPlan && ! $warmupGuardService->canSend($user)) {
                $quotaReached = true;

                break;
            }

            $variant = $variantAssignments[$index] ?? null;

            /** @var CampaignRecipient $recipient */
            $recipient = CampaignRecipient::query()->firstOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'email' => $email,
                ],
                [
                    'contact_id' => $contact->id,
                    'variant_id' => $variant?->id,
                    'phone' => $contact->phone,
                    'status' => 'pending',
                ]
            );

            if (! $recipient->wasRecentlyCreated) {
                continue;
            }

            $delaySeconds = (int) floor($index * $interval);

            if ($campaign->use_sto) {
                $optimalHour = $contactSendTimeService->getOptimalHour($contact, $user);
                if ($optimalHour !== null) {
                    $delaySeconds = max(
                        $delaySeconds,
                        $contactSendTimeService->getNextSendDelay($optimalHour, max(1, (int) $campaign->sto_window_hours))
                    );
                }
            }

            SendCampaignEmailJob::dispatch($recipient->id)
                ->delay(now()->addSeconds($delaySeconds));

            if ($hasWarmupPlan) {
                $warmupGuardService->incrementSentCount($user);
            }

            $contactScoreService->recordEvent($contact, 'campaign_sent', $campaign);

            Activity::query()->create([
                'user_id' => $campaign->user_id,
                'subject_id' => $contact->client_id,
                'subject_type' => Client::class,
                'description' => 'Campaign '.$campaign->name.' sent to contact',
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_type' => $campaign->type,
                    'contact_id' => $contact->id,
                    'variant_id' => $variant?->id,
                    'channel' => 'email',
                ],
            ]);
        }

        if ($quotaReached) {
            $campaign->update([
                'status' => 'scheduled',
                'completed_at' => null,
            ]);

            static::dispatch($campaign->id)
                ->delay(now()->addDay()->setTime(6, 0));

            return;
        }

        if ($campaign->isAbTest() && is_numeric($campaign->ab_auto_select_after_hours) && (int) $campaign->ab_auto_select_after_hours > 0) {
            SelectAbWinnerJob::dispatch($campaign->id)
                ->delay(now()->addHours((int) $campaign->ab_auto_select_after_hours));
        }

        $campaign->update([
            'status' => 'sent',
            'completed_at' => now(),
        ]);

        $freshCampaign = $campaign->fresh();
        if ($freshCampaign instanceof Campaign) {
            $user->notify(new CampaignCompletedNotification($freshCampaign));
        }

        $webhookDispatchService->dispatch('email.campaign_sent', [
            'campaign_id' => $campaign->id,
            'completed_at' => $campaign->completed_at?->toIso8601String(),
        ], $user->id);
    }

    private function resolveThrottleRate(mixed $configuredRate, int $defaultRate): int
    {
        if (is_numeric($configuredRate)) {
            $rate = (int) $configuredRate;

            return $rate > 0 ? $rate : $defaultRate;
        }

        return $defaultRate;
    }

    /**
     * @return array<int, CampaignVariant|null>
     */
    private function resolveVariantAssignments(Campaign $campaign, int $recipientCount): array
    {
        if (! $campaign->isAbTest()) {
            return array_fill(0, $recipientCount, null);
        }

        /** @var \Illuminate\Support\Collection<int, CampaignVariant> $variants */
        $variants = $campaign->variants->sortBy('label')->values();
        if ($variants->count() !== 2) {
            return array_fill(0, $recipientCount, null);
        }

        $variantA = $variants->firstWhere('label', 'A') ?? $variants->get(0);
        $variantB = $variants->firstWhere('label', 'B') ?? $variants->get(1);
        if (! $variantA instanceof CampaignVariant || ! $variantB instanceof CampaignVariant) {
            return array_fill(0, $recipientCount, null);
        }

        $indexes = range(0, max(0, $recipientCount - 1));
        shuffle($indexes);

        $threshold = (int) round(($recipientCount * $variantA->send_percent) / 100);
        $assigned = array_fill(0, $recipientCount, $variantB);

        for ($i = 0; $i < $threshold; $i++) {
            if (! isset($indexes[$i])) {
                break;
            }
            $assigned[$indexes[$i]] = $variantA;
        }

        return $assigned;
    }
}
