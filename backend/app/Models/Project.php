<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string $reference
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $billing_type
 * @property float|null $hourly_rate
 * @property float|null $fixed_price
 * @property float|null $estimated_hours
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'reference',
        'name',
        'description',
        'status',
        'billing_type',
        'hourly_rate',
        'fixed_price',
        'estimated_hours',
        'start_date',
        'deadline',
        'completed_at',
    ];

    /** @var list<string> */
    protected $appends = [
        'total_time_spent',
        'progress_percentage',
        'budget_consumed',
        'total_tasks',
        'completed_tasks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'start_date' => 'date',
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Project>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, Project>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Task, Project>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Invoice, Project>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasManyThrough<TimeEntry, Task, Project>
     */
    public function timeEntries(): HasManyThrough
    {
        return $this->hasManyThrough(TimeEntry::class, Task::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = [
            'draft' => ['draft', 'proposal_sent', 'cancelled'],
            'proposal_sent' => ['proposal_sent', 'in_progress', 'cancelled'],
            'in_progress' => ['in_progress', 'on_hold', 'completed', 'cancelled'],
            'on_hold' => ['on_hold', 'in_progress', 'cancelled', 'completed'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];

        $currentStatus = $this->status;

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true);
    }

    public function getTotalTasksAttribute(): int
    {
        if (array_key_exists('tasks_count', $this->attributes)) {
            return (int) $this->attributes['tasks_count'];
        }

        return $this->tasks()->count();
    }

    public function getCompletedTasksAttribute(): int
    {
        return $this->tasks()->where('status', 'done')->count();
    }

    public function getTotalTimeSpentAttribute(): int
    {
        return (int) $this->timeEntries()->sum('duration_minutes');
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalTasks = $this->total_tasks;
        if ($totalTasks === 0) {
            return 0.0;
        }

        return round(($this->completed_tasks / $totalTasks) * 100, 2);
    }

    public function getBudgetConsumedAttribute(): float
    {
        if ($this->billing_type === 'hourly') {
            $hourlyRate = (float) ($this->hourly_rate ?? 0);
            $hoursSpent = $this->total_time_spent / 60;

            return round($hoursSpent * $hourlyRate, 2);
        }

        $fixedPrice = (float) ($this->fixed_price ?? 0);

        return round($fixedPrice * ($this->progress_percentage / 100), 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'reference' => $this->reference,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'billing_type' => $this->billing_type,
            'deadline' => $this->deadline?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
