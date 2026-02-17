<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $base_currency
 * @property string $target_currency
 * @property float $rate
 * @property Carbon $fetched_at
 * @property string $source
 */
class ExchangeRate extends Model
{
    /** @use HasFactory<\Database\Factories\ExchangeRateFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'fetched_at',
        'rate_date',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'fetched_at' => 'datetime',
            'rate_date' => 'date',
        ];
    }

    /**
     * @param  Builder<ExchangeRate>  $query
     * @return Builder<ExchangeRate>
     */
    public function scopeLatestFor(Builder $query, string $baseCurrency, ?Carbon $asOf = null): Builder
    {
        $query->where('base_currency', strtoupper($baseCurrency));

        if ($asOf !== null) {
            $query->where('fetched_at', '<=', $asOf->copy()->endOfDay());
        }

        return $query->orderByDesc('fetched_at');
    }
}
