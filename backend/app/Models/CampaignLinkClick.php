<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLinkClick extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignLinkClickFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'campaign_id',
        'recipient_id',
        'contact_id',
        'url',
        'clicked_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, CampaignLinkClick>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<CampaignRecipient, CampaignLinkClick>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class, 'recipient_id');
    }

    /**
     * @return BelongsTo<Contact, CampaignLinkClick>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
