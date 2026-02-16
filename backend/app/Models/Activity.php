<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string $subject_id
 * @property string $subject_type
 * @property string $description
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Activity extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'subject_id',
        'subject_type',
        'description',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Activity>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, \App\Models\Activity>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
