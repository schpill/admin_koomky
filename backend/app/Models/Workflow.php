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
 * @property string $name
 * @property string|null $description
 * @property string $trigger_type
 * @property array<string, mixed>|null $trigger_config
 * @property string $status
 * @property string|null $entry_step_id
 */
class Workflow extends Model
{
    /** @use HasFactory<\Database\Factories\WorkflowFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'status',
        'entry_step_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, Workflow>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WorkflowStep, Workflow>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<WorkflowEnrollment, Workflow>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkflowEnrollment::class);
    }

    /**
     * @return BelongsTo<WorkflowStep, Workflow>
     */
    public function entryStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'entry_step_id');
    }

    /**
     * @param  Builder<Workflow>  $query
     * @return Builder<Workflow>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<Workflow>  $query
     * @return Builder<Workflow>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<Workflow>  $query
     * @return Builder<Workflow>
     */
    public function scopeWithTrigger(Builder $query, string $type): Builder
    {
        return $query->where('trigger_type', $type);
    }
}
