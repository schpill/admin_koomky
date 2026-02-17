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
 * @property string|null $project_id
 * @property string|null $converted_invoice_id
 * @property string $number
 * @property string $status
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $valid_until
 * @property float $subtotal
 * @property float $tax_amount
 * @property string|null $discount_type
 * @property float|null $discount_value
 * @property float $discount_amount
 * @property float $total
 * @property string $currency
 * @property string $base_currency
 * @property float|null $exchange_rate
 * @property float|null $base_currency_total
 * @property string|null $notes
 * @property string|null $pdf_path
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 */
class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'project_id',
        'converted_invoice_id',
        'number',
        'status',
        'issue_date',
        'valid_until',
        'subtotal',
        'tax_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total',
        'currency',
        'base_currency',
        'exchange_rate',
        'base_currency_total',
        'notes',
        'pdf_path',
        'sent_at',
        'accepted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'base_currency_total' => 'decimal:2',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Quote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, Quote>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Project, Quote>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Invoice, Quote>
     */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    /**
     * @return MorphMany<LineItem, Quote>
     */
    public function lineItems(): MorphMany
    {
        return $this->morphMany(LineItem::class, 'documentable');
    }

    /**
     * @param  Builder<Quote>  $query
     * @return Builder<Quote>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Quote>  $query
     * @return Builder<Quote>
     */
    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<Quote>  $query
     * @return Builder<Quote>
     */
    public function scopeByDateRange(Builder $query, string $dateFrom, string $dateTo): Builder
    {
        return $query->whereBetween('issue_date', [$dateFrom, $dateTo]);
    }

    /**
     * @param  Builder<Quote>  $query
     * @return Builder<Quote>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('status', 'sent')
            ->whereDate('valid_until', '<', now()->toDateString());
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'draft' => ['draft', 'sent'],
            'sent' => ['sent', 'accepted', 'rejected', 'expired'],
            'accepted' => ['accepted'],
            'rejected' => ['rejected'],
            'expired' => ['expired'],
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
            'project_id' => $this->project_id,
            'number' => $this->number,
            'status' => $this->status,
            'notes' => $this->notes,
            'issue_date' => $this->issue_date->toDateString(),
            'valid_until' => $this->valid_until->toDateString(),
            'total' => (float) $this->total,
            'base_currency' => $this->base_currency,
            'base_currency_total' => $this->base_currency_total !== null ? (float) $this->base_currency_total : null,
            'client_name' => $this->client?->name,
        ];
    }
}
