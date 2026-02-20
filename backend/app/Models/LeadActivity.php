<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $lead_id
 * @property string $type
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Lead $lead
 */
class LeadActivity extends Model
{
    /** @use HasFactory<\Database\Factories\LeadActivityFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'type',
        'content',
        'scheduled_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lead, LeadActivity>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
