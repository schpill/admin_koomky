<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripEnrollment extends Model
{
    /** @use HasFactory<\Database\Factories\DripEnrollmentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'sequence_id',
        'contact_id',
        'current_step_position',
        'status',
        'enrolled_at',
        'last_processed_at',
        'completed_at',
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
     * @return BelongsTo<DripSequence, DripEnrollment>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DripSequence::class, 'sequence_id');
    }

    /**
     * @return BelongsTo<Contact, DripEnrollment>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @param  Builder<DripEnrollment>  $query
     * @return Builder<DripEnrollment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<DripEnrollment>  $query
     * @return Builder<DripEnrollment>
     */
    public function scopeDueForProcessing(Builder $query): Builder
    {
        return $query->active()->where(function (Builder $builder): void {
            $builder->whereNull('last_processed_at')
                ->orWhere('last_processed_at', '<=', now()->subHour());
        });
    }
}
