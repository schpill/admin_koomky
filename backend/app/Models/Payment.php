<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $invoice_id
 * @property float $amount
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string|null $payment_method
 * @property string|null $reference
 * @property string|null $notes
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Invoice, Payment>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
