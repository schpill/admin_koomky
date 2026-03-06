<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignVariant extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignVariantFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'label',
        'subject',
        'content',
        'send_percent',
        'sent_count',
        'open_count',
        'click_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_percent' => 'integer',
            'sent_count' => 'integer',
            'open_count' => 'integer',
            'click_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Campaign, CampaignVariant>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function openRate(): float
    {
        return $this->sent_count > 0
            ? round(($this->open_count / $this->sent_count) * 100, 2)
            : 0.0;
    }

    public function clickRate(): float
    {
        return $this->sent_count > 0
            ? round(($this->click_count / $this->sent_count) * 100, 2)
            : 0.0;
    }
}
