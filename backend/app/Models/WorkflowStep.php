<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $workflow_id
 * @property string $type
 * @property array<string, mixed>|null $config
 * @property string|null $next_step_id
 * @property string|null $else_step_id
 * @property float|null $position_x
 * @property float|null $position_y
 */
class WorkflowStep extends Model
{
    /** @use HasFactory<\Database\Factories\WorkflowStepFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workflow_id',
        'type',
        'config',
        'next_step_id',
        'else_step_id',
        'position_x',
        'position_y',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'position_x' => 'float',
            'position_y' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Workflow, WorkflowStep>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<WorkflowStep, WorkflowStep>
     */
    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_step_id');
    }

    /**
     * @return BelongsTo<WorkflowStep, WorkflowStep>
     */
    public function elseStep(): BelongsTo
    {
        return $this->belongsTo(self::class, 'else_step_id');
    }

    public function isEnd(): bool
    {
        return $this->type === 'end';
    }
}
