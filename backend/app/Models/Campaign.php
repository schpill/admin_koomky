<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $segment_id
 * @property string|null $template_id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property string|null $subject
 * @property string $content
 * @property array<string, mixed>|null $settings
 */
class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory, HasUuids, Searchable;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'segment_id',
        'template_id',
        'name',
        'type',
        'status',
        'subject',
        'content',
        'scheduled_at',
        'started_at',
        'completed_at',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'settings' => 'array',
        ];
    }

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

    /**
     * @return BelongsTo<CampaignTemplate, Campaign>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<CampaignRecipient, Campaign>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /**
     * @return HasMany<CampaignAttachment, Campaign>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CampaignAttachment::class);
    }

    /**
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'draft' => ['draft', 'scheduled', 'sending', 'cancelled'],
            'scheduled' => ['scheduled', 'sending', 'paused', 'cancelled'],
            'sending' => ['sending', 'paused', 'sent', 'cancelled'],
            'paused' => ['paused', 'sending', 'cancelled'],
            'sent' => ['sent'],
            'cancelled' => ['cancelled'],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? [], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $scheduledAt = $this->getAttribute('scheduled_at');
        $createdAt = $this->getAttribute('created_at');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'segment_id' => $this->segment_id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'subject' => $this->subject,
            'content' => $this->content,
            'scheduled_at' => $this->searchDateValue($scheduledAt),
            'created_at' => $this->searchDateValue($createdAt),
        ];
    }

    private function searchDateValue(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
