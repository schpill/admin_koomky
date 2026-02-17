<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property int $decimal_places
 * @property bool $is_active
 */
class Currency extends Model
{
    /** @use HasFactory<\Database\Factories\CurrencyFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Currency>  $query
     * @return Builder<Currency>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
