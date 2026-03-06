<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContactSendTimeService
{
    public function getOptimalHour(Contact $contact, User $user): ?int
    {
        $hours = DB::table('campaign_recipients')
            ->selectRaw('strftime(\'%H\', opened_at) as opened_hour, count(*) as aggregate')
            ->join('campaigns', 'campaigns.id', '=', 'campaign_recipients.campaign_id')
            ->where('campaign_recipients.contact_id', $contact->id)
            ->where('campaigns.user_id', $user->id)
            ->whereNotNull('campaign_recipients.opened_at')
            ->groupBy('opened_hour')
            ->orderByDesc('aggregate')
            ->orderBy('opened_hour')
            ->get();

        if ((int) $hours->sum('aggregate') < 3) {
            return null;
        }

        $optimalHour = $hours->first()?->opened_hour;

        return is_numeric($optimalHour) ? (int) $optimalHour : null;
    }

    public function getNextSendDelay(int $optimalHour, int $windowHours): int
    {
        $now = now();
        $candidate = Carbon::instance($now)->setTime($optimalHour, 0, 0);

        if ($candidate->lessThanOrEqualTo($now)) {
            $candidate->addDay();
        }

        $delay = (int) $now->diffInSeconds($candidate, false);
        $maxDelay = max(1, $windowHours) * 3600;

        return $delay >= 0 && $delay <= $maxDelay ? $delay : 0;
    }
}
