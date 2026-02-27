<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceReminderSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'sequence_id',
        'user_id',
        'started_at',
        'completed_at',
        'is_paused',
        'next_reminder_step_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_paused' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Invoice, InvoiceReminderSchedule>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<ReminderSequence, InvoiceReminderSchedule>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(ReminderSequence::class, 'sequence_id');
    }

    /**
     * @return BelongsTo<User, InvoiceReminderSchedule>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReminderDelivery, InvoiceReminderSchedule>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class, 'invoice_id', 'invoice_id');
    }

    /**
     * @return BelongsTo<ReminderStep, InvoiceReminderSchedule>
     */
    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(ReminderStep::class, 'next_reminder_step_id');
    }

    /**
     * @param  Builder<InvoiceReminderSchedule>  $query
     * @return Builder<InvoiceReminderSchedule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->where('is_paused', false);
    }

    /**
     * @param  Builder<InvoiceReminderSchedule>  $query
     * @return Builder<InvoiceReminderSchedule>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }
}
