<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailWarmupPlan extends Model
{
    /** @use HasFactory<\Database\Factories\EmailWarmupPlanFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'daily_volume_start',
        'daily_volume_max',
        'increment_percent',
        'current_day',
        'current_daily_limit',
        'started_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_volume_start' => 'integer',
            'daily_volume_max' => 'integer',
            'increment_percent' => 'integer',
            'current_day' => 'integer',
            'current_daily_limit' => 'integer',
            'started_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, EmailWarmupPlan>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<EmailWarmupPlan>  $query
     * @return Builder<EmailWarmupPlan>
     */
    public function scopeActiveForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id)->where('status', 'active');
    }

    public function advancePlan(): void
    {
        $nextDay = $this->current_day + 1;
        $nextLimit = (int) min(
            $this->daily_volume_max,
            round($this->daily_volume_start * ((1 + ($this->increment_percent / 100)) ** $nextDay))
        );

        $this->forceFill([
            'current_day' => $nextDay,
            'current_daily_limit' => max($this->daily_volume_start, $nextLimit),
            'status' => $nextLimit >= $this->daily_volume_max ? 'completed' : $this->status,
        ])->save();
    }
}
