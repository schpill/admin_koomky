<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagUsageLog extends Model
{
    /** @use HasFactory<\Database\Factories\RagUsageLogFactory> */
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'question',
        'chunks_used',
        'tokens_used',
        'latency_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'chunks_used' => 'array',
            'tokens_used' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, RagUsageLog> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
