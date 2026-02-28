<?php

namespace App\Models;

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
 * @property string|null $billing_type
 * @property float|null $default_hourly_rate
 * @property string|null $default_currency
 * @property float|null $estimated_hours
 * @property bool $is_public
 */
class ProjectTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectTemplateFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'billing_type',
        'default_hourly_rate',
        'default_currency',
        'estimated_hours',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_hourly_rate' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, ProjectTemplate>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ProjectTemplateTask>
     */
    public function templateTasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'template_id')
            ->orderBy('sort_order');
    }
}