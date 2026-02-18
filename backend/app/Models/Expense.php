<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $expense_category_id
 * @property string|null $project_id
 * @property string|null $client_id
 * @property string $description
 * @property float $amount
 * @property string $currency
 * @property float|null $base_currency_amount
 * @property float $tax_amount
 * @property float|null $tax_rate
 * @property \Illuminate\Support\Carbon $date
 * @property string $payment_method
 * @property bool $is_billable
 * @property bool $is_reimbursable
 * @property \Illuminate\Support\Carbon|null $reimbursed_at
 * @property string|null $vendor
 * @property string|null $reference
 * @property string|null $notes
 * @property string|null $receipt_path
 * @property string|null $receipt_filename
 * @property string|null $receipt_mime_type
 * @property string $status
 */
class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'expense_category_id',
        'project_id',
        'client_id',
        'description',
        'amount',
        'currency',
        'base_currency_amount',
        'tax_amount',
        'tax_rate',
        'date',
        'payment_method',
        'is_billable',
        'is_reimbursable',
        'reimbursed_at',
        'vendor',
        'reference',
        'notes',
        'receipt_path',
        'receipt_filename',
        'receipt_mime_type',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'base_currency_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'date' => 'date',
            'is_billable' => 'boolean',
            'is_reimbursable' => 'boolean',
            'reimbursed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeByCategory(Builder $query, string $categoryId): Builder
    {
        return $query->where('expense_category_id', $categoryId);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeByProject(Builder $query, string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('is_billable', true);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeReimbursable(Builder $query): Builder
    {
        return $query->where('is_reimbursable', true);
    }

    /**
     * @return BelongsTo<User, Expense>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, Expense>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<Project, Expense>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Client, Expense>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'expense_category_id' => $this->expense_category_id,
            'project_id' => $this->project_id,
            'client_id' => $this->client_id,
            'description' => $this->description,
            'vendor' => $this->vendor,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'status' => $this->status,
            'date' => $this->date->toDateString(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
        ];
    }
}
