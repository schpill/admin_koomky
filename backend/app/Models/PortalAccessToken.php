<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $client_id
 * @property string $token
 * @property string $email
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property bool $is_active
 * @property string|null $created_by_user_id
 */
class PortalAccessToken extends Model
{
    /** @use HasFactory<\Database\Factories\PortalAccessTokenFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'client_id',
        'token',
        'email',
        'expires_at',
        'last_used_at',
        'is_active',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * @param  Builder<PortalAccessToken>  $query
     * @return Builder<PortalAccessToken>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<PortalAccessToken>  $query
     * @return Builder<PortalAccessToken>
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * @return BelongsTo<Client, PortalAccessToken>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, PortalAccessToken>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<PortalActivityLog, PortalAccessToken>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(PortalActivityLog::class);
    }
}
