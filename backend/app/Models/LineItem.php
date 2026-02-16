<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property float $vat_rate
 * @property float $total
 * @property int $sort_order
 */
class LineItem extends Model
{
    /** @use HasFactory<\Database\Factories\LineItemFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'description',
        'quantity',
        'unit_price',
        'vat_rate',
        'total',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LineItem $lineItem): void {
            $quantity = round((float) $lineItem->quantity, 2);
            $unitPrice = round((float) $lineItem->unit_price, 2);

            $lineItem->quantity = $quantity;
            $lineItem->unit_price = $unitPrice;
            $lineItem->total = round($quantity * $unitPrice, 2);
        });
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, LineItem>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
