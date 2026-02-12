<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Client extends Model
{
    use HasFactory, HasUuids, Searchable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'reference',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'siret',
        'vat_number',
        'website',
        'notes',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Scope to only include active clients.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to only include archived clients.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * User relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contacts relationship.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Tags relationship.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'client_tag');
    }

    /**
     * Activities relationship.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Invoices relationship (Phase 2).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Projects relationship (Phase 2).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the client's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the client's display name.
     * Returns company name if available, otherwise full name.
     */
    public function getNameAttribute(): string
    {
        return $this->company_name ?: $this->getFullNameAttribute();
    }

    /**
     * Get the client's company name.
     */
    public function getCompanyAttribute(): ?string
    {
        return $this->company_name;
    }

    /**
     * Get the client's status.
     */
    public function getStatusAttribute(): string
    {
        return $this->archived_at ? 'archived' : 'active';
    }

    /**
     * Get the client's billing address.
     */
    public function getBillingAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address,
            trim("{$this->postal_code} {$this->city}"),
            $this->country,
        ]);

        return $parts ? implode("\n", $parts) : null;
    }

    /**
     * Meilisearch indexing configuration.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'archived_at' => $this->archived_at ? $this->archived_at->toIso8601String() : null,
        ];
    }
}
