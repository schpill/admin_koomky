<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $score
 */
class DocumentChunk extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentChunkFactory> */
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'user_id',
        'chunk_index',
        'content',
        'embedding',
        'token_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'token_count' => 'integer',
            'chunk_index' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Document, DocumentChunk> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return BelongsTo<User, DocumentChunk> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
