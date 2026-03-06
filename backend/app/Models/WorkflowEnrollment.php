<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEnrollment extends Model
{
    /** @use HasFactory<\Database\Factories\WorkflowEnrollmentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workflow_id',
        'contact_id',
        'current_step_id',
        'status',
        'enrolled_at',
        'last_processed_at',
        'completed_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'last_processed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workflow, WorkflowEnrollment>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<Contact, WorkflowEnrollment>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<WorkflowStep, WorkflowEnrollment>
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    /**
     * @param  Builder<WorkflowEnrollment>  $query
     * @return Builder<WorkflowEnrollment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<WorkflowEnrollment>  $query
     * @return Builder<WorkflowEnrollment>
     */
    public function scopeDueForProcessing(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('current_step_id')
            ->where(function (Builder $builder): void {
                $builder->whereNull('last_processed_at')
                    ->orWhere('last_processed_at', '<=', now());
            });
    }
}
