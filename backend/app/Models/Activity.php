<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'subject_type',
        'subject_id',
        'type',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Activity types enum.
     */
    const TYPE_FINANCIAL = 'financial';

    const TYPE_PROJECT = 'project';

    const TYPE_COMMUNICATION = 'communication';

    const TYPE_NOTE = 'note';

    const TYPE_SYSTEM = 'system';

    /**
     * User relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Client relationship.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Polymorphic subject relationship.
     */
    public function subject()
    {
        return $this->morphTo();
    }
}
