<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $template_id
 * @property string $title
 * @property string|null $description
 * @property float|null $estimated_hours
 * @property string $priority
 * @property int $sort_order
 */
class ProjectTemplateTask extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectTemplateTaskFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'template_id',
        'title',
        'description',
        'estimated_hours',
        'priority',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ProjectTemplate, ProjectTemplateTask>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Factory compatibility alias for implicit relation name resolution.
     *
     * @return BelongsTo<ProjectTemplate, ProjectTemplateTask>
     */
    public function projectTemplate(): BelongsTo
    {
        return $this->template();
    }

    /**
     * Scope to order tasks by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
