<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    use HasUuids;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'original_filename',
        'storage_path',
        'storage_disk',
        'mime_type',
        'document_type',
        'script_language',
        'file_size',
        'version',
        'tags',
        'last_sent_at',
        'last_sent_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'document_type' => DocumentType::class,
        'last_sent_at' => 'datetime',
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Get the user that owns the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client associated with the document.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include documents of a certain type.
     *
     * @param  Builder<Document>  $query
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('document_type', $type);
    }

    /**
     * Scope a query to only include documents for a specific client.
     *
     * @param  Builder<Document>  $query
     */
    public function scopeByClient(Builder $query, string $clientId): void
    {
        $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to only include documents with a specific tag.
     *
     * @param  Builder<Document>  $query
     */
    public function scopeByTag(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'tags' => $this->tags,
            'document_type' => $this->document_type->value,
            'script_language' => $this->script_language,
            'file_size' => $this->file_size,
            'created_at' => $this->created_at?->timestamp,
        ];
    }
}
