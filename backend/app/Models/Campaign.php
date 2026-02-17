<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temporary minimal model introduced in Sprint 9 to support Segment relationship typing.
 * Full campaign implementation lands in Sprint 10.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $segment_id
 */
class Campaign extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'segment_id',
    ];

    /**
     * @return BelongsTo<User, Campaign>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Segment, Campaign>
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
