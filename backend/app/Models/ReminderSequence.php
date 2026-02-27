<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderSequence extends Model
{
    /** @use HasFactory<\Database\Factories\ReminderSequenceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, ReminderSequence>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReminderStep, ReminderSequence>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ReminderStep::class, 'sequence_id')->orderBy('step_number');
    }

    /**
     * @return HasMany<InvoiceReminderSchedule, ReminderSequence>
     */
    public function invoiceSchedules(): HasMany
    {
        return $this->hasMany(InvoiceReminderSchedule::class, 'sequence_id');
    }

    /**
     * @param  Builder<ReminderSequence>  $query
     * @return Builder<ReminderSequence>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ReminderSequence>  $query
     * @return Builder<ReminderSequence>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
