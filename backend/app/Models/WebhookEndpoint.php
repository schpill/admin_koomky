<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property array<int, string> $events
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_triggered_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebhookDelivery> $deliveries
 */
class WebhookEndpoint extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookEndpointFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, WebhookEndpoint>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WebhookDelivery, WebhookEndpoint>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_endpoint_id');
    }

    /**
     * Scope a query to only include active endpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WebhookEndpoint>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WebhookEndpoint>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if endpoint is subscribed to a given event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
