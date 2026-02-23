<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory, HasUuids, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'category',
        'tags',
        'deadline',
        'user_id',
        'assigned_to',
        'client_id',
        'project_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_at' => 'datetime',
        'tags' => 'array',
        'status' => \App\Enums\TicketStatus::class,
        'priority' => \App\Enums\TicketPriority::class,
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<TicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /** @return BelongsToMany<Document, $this> */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'ticket_documents');
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return 'tickets';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // Customize the data array for searchable fields.
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'assigned_to' => $this->assigned_to,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'category' => $this->category,
            'tags' => $this->tags,
            'deadline' => $this->deadline?->timestamp,
            'created_at' => $this->created_at?->timestamp,
            'updated_at' => $this->updated_at?->timestamp,
        ];
    }

    /**
     * Get the Meilisearch index settings for the model.
     *
     * @return array<string, mixed>
     */
    public function searchableConfiguration(): array
    {
        return [
            'searchableAttributes' => ['title', 'description'],
            'filterableAttributes' => ['user_id', 'assigned_to', 'client_id', 'project_id', 'status', 'priority', 'category', 'tags'],
            'sortableAttributes' => ['created_at', 'updated_at', 'deadline', 'priority'],
        ];
    }

    // The following methods are for compatibility with older Scout versions or specific needs.
    // In modern Scout (v9+ with Meilisearch), searchableConfiguration is preferred.
    /** @return list<string> */
    public function getFilterableAttributes(): array
    {
        return ['user_id', 'assigned_to', 'client_id', 'project_id', 'status', 'priority', 'category', 'tags'];
    }

    /** @return list<string> */
    public function getSortableAttributes(): array
    {
        return ['created_at', 'updated_at', 'deadline', 'priority'];
    }

    public function isOverdue(): bool
    {
        return $this->deadline &&
               $this->deadline->isPast() &&
               ! in_array($this->status, [\App\Enums\TicketStatus::Resolved, \App\Enums\TicketStatus::Closed]);
    }
}
