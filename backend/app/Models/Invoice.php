<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string|null $project_id
 * @property string|null $recurring_invoice_profile_id
 * @property string $number
 * @property string $status
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $due_date
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
 * @property string|null $payment_terms
 * @property string|null $pdf_path
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $paid_at
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'project_id',
        'recurring_invoice_profile_id',
        'number',
        'status',
        'issue_date',
        'due_date',
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
        'payment_terms',
        'pdf_path',
        'sent_at',
        'viewed_at',
        'paid_at',
    ];

    /** @var list<string> */
    protected $appends = [
        'amount_paid',
        'balance_due',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'base_currency_total' => 'decimal:2',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Invoice>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, Invoice>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Project, Invoice>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<RecurringInvoiceProfile, Invoice>
     */
    public function recurringProfile(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoiceProfile::class, 'recurring_invoice_profile_id');
    }

    /**
     * @return MorphMany<LineItem, Invoice>
     */
    public function lineItems(): MorphMany
    {
        return $this->morphMany(LineItem::class, 'documentable');
    }

    /**
     * @return HasMany<Payment, Invoice>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<CreditNote, Invoice>
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * @return HasMany<PaymentIntent, Invoice>
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeByDateRange(Builder $query, string $dateFrom, string $dateTo): Builder
    {
        return $query->whereBetween('issue_date', [$dateFrom, $dateTo]);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['sent', 'viewed'])
            ->whereDate('due_date', '<', now()->toDateString());
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'draft' => ['draft', 'sent', 'cancelled'],
            'sent' => ['sent', 'viewed', 'partially_paid', 'paid', 'overdue', 'cancelled'],
            'viewed' => ['viewed', 'partially_paid', 'paid', 'overdue', 'cancelled'],
            'partially_paid' => ['partially_paid', 'paid', 'overdue'],
            'overdue' => ['overdue', 'partially_paid', 'paid'],
            'paid' => ['paid'],
            'cancelled' => ['cancelled'],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? [], true);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return round(max(0, ((float) $this->total) - $this->amount_paid), 2);
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
            'recurring_invoice_profile_id' => $this->recurring_invoice_profile_id,
            'number' => $this->number,
            'status' => $this->status,
            'notes' => $this->notes,
            'issue_date' => $this->issue_date->toDateString(),
            'total' => (float) $this->total,
            'base_currency' => $this->base_currency,
            'base_currency_total' => $this->base_currency_total !== null ? (float) $this->base_currency_total : null,
            'client_name' => $this->client?->name,
        ];
    }
}
