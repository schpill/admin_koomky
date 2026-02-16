<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $project_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property float|null $estimated_hours
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property int $sort_order
 */
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'estimated_hours',
        'due_date',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, Task>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<TimeEntry, Task>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return HasMany<TaskAttachment, Task>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * @return BelongsToMany<Task, Task>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<Task, Task>
     */
    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done']);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if ($newStatus === 'in_progress' && ! $this->dependenciesCompleted()) {
            return false;
        }

        return true;
    }

    public function dependenciesCompleted(): bool
    {
        return ! $this->dependencies()->where('status', '!=', 'done')->exists();
    }

    public function hasCircularDependency(string $dependsOnTaskId): bool
    {
        if ($dependsOnTaskId === $this->id) {
            return true;
        }

        $visited = [];
        $stack = [$dependsOnTaskId];

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === null) {
                continue;
            }

            if ($current === $this->id) {
                return true;
            }

            if (in_array($current, $visited, true)) {
                continue;
            }

            $visited[] = $current;

            $nextDependencies = self::query()
                ->where('id', $current)
                ->first()?->dependencies()
                ->pluck('tasks.id')
                ->all() ?? [];

            foreach ($nextDependencies as $nextDependency) {
                $stack[] = $nextDependency;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->project?->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toDateString(),
        ];
    }
}
