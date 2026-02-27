<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderStep extends Model
{
    /** @use HasFactory<\Database\Factories\ReminderStepFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'sequence_id',
        'step_number',
        'delay_days',
        'subject',
        'body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step_number' => 'integer',
            'delay_days' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ReminderSequence, ReminderStep>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(ReminderSequence::class, 'sequence_id');
    }

    /**
     * @return HasMany<ReminderDelivery, ReminderStep>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class, 'reminder_step_id');
    }

    /**
     * @param  Builder<ReminderStep>  $query
     * @return Builder<ReminderStep>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('step_number');
    }
}
