<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $calendar_connection_id
 * @property string|null $external_id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon $end_at
 * @property bool $all_day
 * @property string|null $location
 * @property string $type
 * @property string|null $eventable_type
 * @property string|null $eventable_id
 * @property string|null $recurrence_rule
 * @property string $sync_status
 * @property \Illuminate\Support\Carbon|null $external_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarEventFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'calendar_connection_id',
        'external_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'type',
        'eventable_type',
        'eventable_id',
        'recurrence_rule',
        'sync_status',
        'external_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'external_updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, CalendarEvent>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CalendarConnection, CalendarEvent>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(CalendarConnection::class, 'calendar_connection_id');
    }

    /**
     * @return MorphTo<Model, CalendarEvent>
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public function scopeInDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): Builder
    {
        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->whereDate('start_at', '>=', $dateFrom);
        }

        if (is_string($dateTo) && $dateTo !== '') {
            $query->whereDate('start_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public function scopeByType(Builder $query, ?string $type): Builder
    {
        if (is_string($type) && $type !== '') {
            $query->where('type', $type);
        }

        return $query;
    }

    public function isLocallyNewerThan(?Carbon $remoteUpdatedAt): bool
    {
        if ($remoteUpdatedAt === null || $this->updated_at === null) {
            return false;
        }

        return $this->updated_at->greaterThan($remoteUpdatedAt);
    }
}
