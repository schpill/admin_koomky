<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContactSendTimeService
{
    public function getOptimalHour(Contact $contact, User $user): ?int
    {
        $timezone = $this->resolveTimezone($contact->timezone);

        /** @var Collection<int, object{opened_at:string}> $opens */
        $opens = DB::table('campaign_recipients')
            ->select('campaign_recipients.opened_at')
            ->join('campaigns', 'campaigns.id', '=', 'campaign_recipients.campaign_id')
            ->where('campaign_recipients.contact_id', $contact->id)
            ->where('campaigns.user_id', $user->id)
            ->whereNotNull('campaign_recipients.opened_at')
            ->get();

        if ($opens->count() < 3) {
            return null;
        }

        $hours = $opens
            ->map(function (object $row) use ($timezone): int {
                return Carbon::parse($row->opened_at, 'UTC')
                    ->setTimezone($timezone)
                    ->hour;
            })
            ->countBy()
            ->sortDesc();

        $optimalHour = $hours->keys()->first();

        return is_numeric($optimalHour) ? (int) $optimalHour : null;
    }

    public function getNextSendDelay(int $optimalHour, int $windowHours, ?string $timezone = null): int
    {
        $resolvedTimezone = $this->resolveTimezone($timezone);
        $nowUtc = now()->utc();
        $localNow = $nowUtc->copy()->setTimezone($resolvedTimezone);
        $candidateLocal = $localNow->copy()->setTime($optimalHour, 0, 0);

        if ($candidateLocal->lessThanOrEqualTo($localNow)) {
            $candidateLocal->addDay();
        }

        $candidateUtc = $candidateLocal->copy()->setTimezone('UTC');
        $delay = (int) $nowUtc->diffInSeconds($candidateUtc, false);
        $maxDelay = max(1, $windowHours) * 3600;

        return $delay >= 0 && $delay <= $maxDelay ? $delay : 0;
    }

    private function resolveTimezone(?string $timezone): string
    {
        return is_string($timezone) && $timezone !== '' ? $timezone : (string) config('app.timezone', 'UTC');
    }
}
