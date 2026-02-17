<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string $name
 * @property string $frequency
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon $next_due_date
 * @property int|null $day_of_month
 * @property array<int, array<string, mixed>> $line_items
 * @property string|null $notes
 * @property int $payment_terms_days
 * @property float|null $tax_rate
 * @property float|null $discount_percent
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_generated_at
 * @property int $occurrences_generated
 * @property int|null $max_occurrences
 * @property bool $auto_send
 * @property string $currency
 */
class RecurringInvoiceProfile extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringInvoiceProfileFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'frequency',
        'start_date',
        'end_date',
        'next_due_date',
        'day_of_month',
        'line_items',
        'notes',
        'payment_terms_days',
        'tax_rate',
        'discount_percent',
        'status',
        'last_generated_at',
        'occurrences_generated',
        'max_occurrences',
        'auto_send',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'line_items' => 'array',
            'payment_terms_days' => 'integer',
            'tax_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'last_generated_at' => 'datetime',
            'occurrences_generated' => 'integer',
            'max_occurrences' => 'integer',
            'auto_send' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, RecurringInvoiceProfile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, RecurringInvoiceProfile>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Invoice, RecurringInvoiceProfile>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_invoice_profile_id');
    }

    /**
     * @param  Builder<RecurringInvoiceProfile>  $query
     * @return Builder<RecurringInvoiceProfile>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<RecurringInvoiceProfile>  $query
     * @return Builder<RecurringInvoiceProfile>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->whereDate('next_due_date', '<=', now()->toDateString());
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
            'name' => $this->name,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'next_due_date' => $this->next_due_date->toDateString(),
            'currency' => $this->currency,
        ];
    }
}
