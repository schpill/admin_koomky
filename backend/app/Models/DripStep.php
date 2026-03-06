<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripStep extends Model
{
    /** @use HasFactory<\Database\Factories\DripStepFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'sequence_id',
        'position',
        'delay_hours',
        'condition',
        'subject',
        'content',
        'template_id',
    ];

    /**
     * @return BelongsTo<DripSequence, DripStep>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DripSequence::class, 'sequence_id');
    }

    /**
     * @return BelongsTo<CampaignTemplate, DripStep>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id');
    }

    public function evaluateCondition(DripEnrollment $enrollment): bool
    {
        if ($this->condition === 'none') {
            return true;
        }

        $previousPosition = $this->position - 1;
        if ($previousPosition < 1) {
            return true;
        }

        $recipient = CampaignRecipient::query()
            ->where('contact_id', $enrollment->contact_id)
            ->latest('created_at')
            ->get()
            ->first(function (CampaignRecipient $candidate) use ($enrollment, $previousPosition): bool {
                /** @var array<string, mixed> $metadata */
                $metadata = $candidate->metadata ?? [];

                return ($metadata['drip_enrollment_id'] ?? null) === $enrollment->id
                    && (int) ($metadata['drip_step_position'] ?? 0) === $previousPosition;
            });

        if ($recipient === null) {
            return false;
        }

        if ($this->condition === 'if_opened') {
            return $recipient->opened_at !== null;
        }

        if ($this->condition === 'if_clicked') {
            return $recipient->clicked_at !== null;
        }

        return $recipient->opened_at === null;
    }
}
