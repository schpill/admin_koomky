<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $company_name
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string $source
 * @property string $status
 * @property float|null $estimated_value
 * @property string $currency
 * @property int|null $probability
 * @property \Illuminate\Support\Carbon|null $expected_close_date
 * @property string|null $notes
 * @property string|null $lost_reason
 * @property string|null $won_client_id
 * @property \Illuminate\Support\Carbon|null $converted_at
 * @property int $pipeline_position
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Client|null $wonClient
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadActivity> $activities
 */
class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'source',
        'status',
        'estimated_value',
        'currency',
        'probability',
        'expected_close_date',
        'notes',
        'lost_reason',
        'won_client_id',
        'converted_at',
        'pipeline_position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'converted_at' => 'datetime',
            'pipeline_position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, Lead>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, Lead>
     */
    public function wonClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'won_client_id');
    }

    /**
     * @return HasMany<LeadActivity, Lead>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by source.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to only include open deals.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeOpenDeals(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['won', 'lost']);
    }

    /**
     * Scope a query to only include closed deals.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeClosedDeals(Builder $query): Builder
    {
        return $query->whereIn('status', ['won', 'lost']);
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'company_name' => $this->company_name ?? '',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?? '',
            'notes' => $this->notes ?? '',
        ];
    }

    /**
     * Check if lead can be converted to client.
     */
    public function canConvert(): bool
    {
        return $this->status === 'won' && $this->won_client_id === null;
    }

    /**
     * Check if lead is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['won', 'lost'], true);
    }
}
