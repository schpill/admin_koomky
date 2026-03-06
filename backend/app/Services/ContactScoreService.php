<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactScoreEvent;
use App\Models\ScoringRule;
use Illuminate\Support\Collection;

class ContactScoreService
{
    /**
     * @var array<string, array{points:int, expiry_days:int|null}>
     */
    private const DEFAULT_RULES = [
        'email_opened' => ['points' => 10, 'expiry_days' => 90],
        'email_clicked' => ['points' => 20, 'expiry_days' => 90],
        'email_bounced' => ['points' => -5, 'expiry_days' => null],
        'email_unsubscribed' => ['points' => -50, 'expiry_days' => null],
        'campaign_sent' => ['points' => 1, 'expiry_days' => 180],
    ];

    public function recordEvent(Contact $contact, string $event, ?Campaign $campaign = null): void
    {
        $user = $contact->client?->user;
        if ($user === null) {
            return;
        }

        $this->ensureDefaultRules($user->id);

        $rule = ScoringRule::query()
            ->where('user_id', $user->id)
            ->where('event', $event)
            ->where('is_active', true)
            ->first();

        if (! $rule instanceof ScoringRule) {
            return;
        }

        ContactScoreEvent::query()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'event' => $event,
            'points' => $rule->points,
            'source_campaign_id' => $campaign?->id,
            'expires_at' => $rule->expiry_days !== null ? now()->addDays($rule->expiry_days) : null,
            'created_at' => now(),
        ]);

        $this->recalculate($contact);
    }

    public function recalculate(Contact $contact): int
    {
        $score = (int) ContactScoreEvent::query()
            ->where('contact_id', $contact->id)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $contact->forceFill([
            'email_score' => $score,
            'email_score_updated_at' => now(),
        ])->save();

        return $score;
    }

    /**
     * @return Collection<int, ContactScoreEvent>
     */
    public function getHistory(Contact $contact): Collection
    {
        return ContactScoreEvent::query()
            ->where('contact_id', $contact->id)
            ->orderByDesc('created_at')
            ->get();
    }

    private function ensureDefaultRules(string $userId): void
    {
        $existingEvents = ScoringRule::query()
            ->where('user_id', $userId)
            ->pluck('event')
            ->all();

        foreach (self::DEFAULT_RULES as $event => $rule) {
            if (in_array($event, $existingEvents, true)) {
                continue;
            }

            ScoringRule::query()->create([
                'user_id' => $userId,
                'event' => $event,
                'points' => $rule['points'],
                'expiry_days' => $rule['expiry_days'],
                'is_active' => true,
            ]);
        }
    }
}
