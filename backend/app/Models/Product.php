<?php

namespace App\Models;

use App\Enums\ProductPriceType;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'type',
        'description',
        'short_description',
        'price',
        'price_type',
        'currency_code',
        'vat_rate',
        'duration',
        'duration_unit',
        'sku',
        'tags',
        'is_active',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'duration' => 'integer',
        'tags' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'type' => ProductType::class,
        'price_type' => ProductPriceType::class,
    ];

    /**
     * Get the user that owns the product.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sales for the product.
     *
     * @return HasMany<ProductSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    /**
     * Get the campaigns associated with the product.
     *
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'product_campaigns')
            ->withPivot('generation_model', 'generated_at')
            ->withTimestamps();
    }

    /**
     * Get the line items for the product.
     *
     * @return HasMany<LineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param Builder<Product> $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include archived (soft deleted) products.
     *
     * @param Builder<Product> $query
     */
    public function scopeArchived(Builder $query): void
    {
        $query->onlyTrashed();
    }

    /**
     * Scope a query to only include products of a certain type.
     *
     * @param Builder<Product> $query
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope a query to only include products with a specific tag.
     *
     * @param Builder<Product> $query
     */
    public function scopeByTag(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'tags' => $this->tags,
            'price' => $this->price,
            'price_type' => $this->price_type?->value,
            'currency_code' => $this->currency_code,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * Get the Meilisearch index settings for the model.
     *
     * @return array<string, mixed>
     */
    public function searchableConfiguration(): array
    {
        return [
            'searchableAttributes' => ['name', 'description', 'short_description', 'tags'],
            'filterableAttributes' => ['user_id', 'type', 'is_active'],
            'sortableAttributes' => ['created_at', 'name', 'price'],
        ];
    }

    /** @return list<string> */
    public function getFilterableAttributes(): array
    {
        return ['user_id', 'type', 'is_active'];
    }

    /** @return list<string> */
    public function getSortableAttributes(): array
    {
        return ['created_at', 'name', 'price'];
    }
}
