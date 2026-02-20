<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $webhook_endpoint_id
 * @property string $event
 * @property array<string, mixed> $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int $attempt_count
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\WebhookEndpoint $endpoint
 */
class WebhookDelivery extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'attempt_count',
        'delivered_at',
        'failed_at',
        'next_retry_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'attempt_count' => 'integer',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WebhookEndpoint, WebhookDelivery>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /**
     * Scope a query to only include pending deliveries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WebhookDelivery>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WebhookDelivery>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('delivered_at')->whereNull('failed_at');
    }

    /**
     * Scope a query to only include failed deliveries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WebhookDelivery>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WebhookDelivery>
     */
    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('failed_at');
    }

    /**
     * Scope a query to only include delivered deliveries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WebhookDelivery>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WebhookDelivery>
     */
    public function scopeDelivered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Mark delivery as successful.
     */
    public function markAsDelivered(int $responseStatus, ?string $responseBody = null): void
    {
        $this->response_status = $responseStatus;
        $this->response_body = $responseBody;
        $this->delivered_at = now();
        $this->next_retry_at = null;
        $this->save();
    }

    /**
     * Mark delivery as failed.
     */
    public function markAsFailed(?int $responseStatus = null, ?string $responseBody = null): void
    {
        $this->response_status = $responseStatus;
        $this->response_body = $responseBody;
        $this->attempt_count++;
    }

    /**
     * Check if delivery can be retried.
     */
    public function canRetry(): bool
    {
        return $this->attempt_count < 5;
    }

    /**
     * Calculate next retry time with exponential backoff.
     */
    public function calculateNextRetry(): \Illuminate\Support\Carbon
    {
        $delays = [1, 5, 30, 300, 1800]; // seconds: 1s, 5s, 30s, 5min, 30min

        return now()->addSeconds($delays[min($this->attempt_count - 1, count($delays) - 1)]);
    }
}
