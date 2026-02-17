<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $provider
 * @property string $name
 * @property array<string, mixed> $credentials
 * @property string|null $calendar_id
 * @property bool $sync_enabled
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CalendarConnection extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarConnectionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'provider',
        'name',
        'credentials',
        'calendar_id',
        'sync_enabled',
        'last_synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'sync_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, CalendarConnection>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CalendarEvent, CalendarConnection>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * @param  Builder<CalendarConnection>  $query
     * @return Builder<CalendarConnection>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('sync_enabled', true);
    }
}
