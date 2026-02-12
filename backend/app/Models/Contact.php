<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $client_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $role
 * @property bool $is_primary
 * @property string|null $notes
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $name
 * @property-read \App\Models\Client $client
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 */
class Contact extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'role',
        'is_primary',
        'notes',
        'position',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Scope to only include primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Client relationship.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Activities relationship.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    /**
     * Get the contact's full name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
