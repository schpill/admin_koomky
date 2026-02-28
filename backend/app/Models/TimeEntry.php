<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $task_id
 * @property int $duration_minutes
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $description
 * @property bool $is_billed
 * @property \Illuminate\Support\Carbon|null $billed_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property bool $is_running
 */
class TimeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimeEntryFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'task_id',
        'duration_minutes',
        'date',
        'description',
        'is_billed',
        'billed_at',
        'started_at',
        'is_running',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_billed' => 'boolean',
            'billed_at' => 'datetime',
            'started_at' => 'datetime',
            'is_running' => 'boolean',
        ];
    }

    /**
     * Scope to get only running time entries.
     */
    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }

    /**
     * Compute duration in minutes from started_at to now.
     */
    public function computeDurationMinutes(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($this->started_at) / 60);
    }

    /**
     * @return BelongsTo<User, TimeEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task, TimeEntry>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
