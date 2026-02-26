<?php

namespace App\Models;

use App\Enums\ProductSaleStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSale extends Model
{
    /** @use HasFactory<\Database\Factories\ProductSaleFactory> */
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_sales';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'client_id',
        'invoice_id',
        'quote_id',
        'quantity',
        'unit_price',
        'total_price',
        'currency_code',
        'status',
        'sold_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'sold_at' => 'datetime',
        'status' => ProductSaleStatus::class,
    ];

    /**
     * Get the product that was sold.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the sale.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client associated with the sale.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice associated with the sale.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the quote associated with the sale.
     *
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Scope a query to only include sales with a specific status.
     *
     * @param Builder<ProductSale> $query
     */
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope a query to only include confirmed sales.
     *
     * @param Builder<ProductSale> $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', ProductSaleStatus::Confirmed);
    }

    /**
     * Scope a query to only include sales for a specific period.
     *
     * @param Builder<ProductSale> $query
     */
    public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('sold_at', [$from, $to]);
    }
}
