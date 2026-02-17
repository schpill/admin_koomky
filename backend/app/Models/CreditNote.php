<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string $invoice_id
 * @property string $number
 * @property string $status
 * @property \Illuminate\Support\Carbon $issue_date
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total
 * @property string $currency
 * @property string $base_currency
 * @property float|null $exchange_rate
 * @property float|null $base_currency_total
 * @property string|null $reason
 * @property string|null $pdf_path
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $applied_at
 */
class CreditNote extends Model
{
    /** @use HasFactory<\Database\Factories\CreditNoteFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_id',
        'number',
        'status',
        'issue_date',
        'subtotal',
        'tax_amount',
        'total',
        'currency',
        'base_currency',
        'exchange_rate',
        'base_currency_total',
        'reason',
        'pdf_path',
        'sent_at',
        'applied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'base_currency_total' => 'decimal:2',
            'sent_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, CreditNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, CreditNote>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Invoice, CreditNote>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return MorphMany<LineItem, CreditNote>
     */
    public function lineItems(): MorphMany
    {
        return $this->morphMany(LineItem::class, 'documentable');
    }

    /**
     * @param  Builder<CreditNote>  $query
     * @return Builder<CreditNote>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<CreditNote>  $query
     * @return Builder<CreditNote>
     */
    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<CreditNote>  $query
     * @return Builder<CreditNote>
     */
    public function scopeByInvoice(Builder $query, string $invoiceId): Builder
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * @param  Builder<CreditNote>  $query
     * @return Builder<CreditNote>
     */
    public function scopeByDateRange(Builder $query, string $dateFrom, string $dateTo): Builder
    {
        return $query->whereBetween('issue_date', [$dateFrom, $dateTo]);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'draft' => ['draft', 'sent'],
            'sent' => ['sent', 'applied'],
            'applied' => ['applied'],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? [], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'invoice_id' => $this->invoice_id,
            'number' => $this->number,
            'status' => $this->status,
            'reason' => $this->reason,
            'issue_date' => $this->issue_date->toDateString(),
            'total' => (float) $this->total,
            'base_currency' => $this->base_currency,
            'base_currency_total' => $this->base_currency_total !== null ? (float) $this->base_currency_total : null,
            'client_name' => $this->client?->name,
            'invoice_number' => $this->invoice?->number,
        ];
    }
}
