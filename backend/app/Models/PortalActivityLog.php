<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $client_id
 * @property string|null $portal_access_token_id
 * @property string $action
 * @property string|null $entity_type
 * @property string|null $entity_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class PortalActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\PortalActivityLogFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'client_id',
        'portal_access_token_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * @param  Builder<PortalActivityLog>  $query
     * @return Builder<PortalActivityLog>
     */
    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<PortalActivityLog>  $query
     * @return Builder<PortalActivityLog>
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * @return BelongsTo<Client, PortalActivityLog>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<PortalAccessToken, PortalActivityLog>
     */
    public function portalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PortalAccessToken::class);
    }
}
