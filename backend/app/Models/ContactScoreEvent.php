<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactScoreEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ContactScoreEventFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'contact_id',
        'event',
        'points',
        'source_campaign_id',
        'expires_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contact, ContactScoreEvent>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Campaign, ContactScoreEvent>
     */
    public function sourceCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'source_campaign_id');
    }
}
