<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DripSequence extends Model
{
    /** @use HasFactory<\Database\Factories\DripSequenceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'trigger_event',
        'trigger_campaign_id',
        'status',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, DripSequence>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Campaign, DripSequence>
     */
    public function triggerCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'trigger_campaign_id');
    }

    /**
     * @return HasMany<DripStep, DripSequence>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(DripStep::class, 'sequence_id')->orderBy('position');
    }

    /**
     * @return HasMany<DripEnrollment, DripSequence>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(DripEnrollment::class, 'sequence_id');
    }

    /**
     * @param  Builder<DripSequence>  $query
     * @return Builder<DripSequence>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<DripSequence>  $query
     * @return Builder<DripSequence>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
