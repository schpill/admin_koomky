<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDelivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'reminder_step_id',
        'user_id',
        'sent_at',
        'status',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, ReminderDelivery>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<ReminderStep, ReminderDelivery>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ReminderStep::class, 'reminder_step_id');
    }

    /**
     * @return BelongsTo<User, ReminderDelivery>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<ReminderDelivery>  $query
     * @return Builder<ReminderDelivery>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * @param  Builder<ReminderDelivery>  $query
     * @return Builder<ReminderDelivery>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * @param  Builder<ReminderDelivery>  $query
     * @return Builder<ReminderDelivery>
     */
    public function scopeSkipped(Builder $query): Builder
    {
        return $query->where('status', 'skipped');
    }
}
