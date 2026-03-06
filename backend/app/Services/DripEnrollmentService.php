<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\Segment;
use App\Models\User;

class DripEnrollmentService
{
    public function __construct(
        private readonly SegmentFilterEngine $segmentFilterEngine,
        private readonly SuppressionService $suppressionService
    ) {}

    public function enroll(Contact $contact, DripSequence $sequence): DripEnrollment
    {
        $existing = DripEnrollment::query()
            ->where('sequence_id', $sequence->id)
            ->where('contact_id', $contact->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $status = 'active';
        if ($sequence->user !== null && is_string($contact->email) && $contact->email !== '') {
            if ($this->suppressionService->isSuppressed($sequence->user, $contact->email)) {
                $status = 'cancelled';
            }
        }

        return DripEnrollment::query()->create([
            'sequence_id' => $sequence->id,
            'contact_id' => $contact->id,
            'current_step_position' => 0,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    public function enrollSegment(Segment $segment, DripSequence $sequence): int
    {
        $user = $sequence->user;
        if (! $user instanceof User) {
            return 0;
        }

        $contacts = $this->segmentFilterEngine
            ->apply($user, (array) $segment->filters)
            ->get();

        $count = 0;
        foreach ($contacts as $contact) {
            $this->enroll($contact, $sequence);
            $count++;
        }

        return $count;
    }
}
