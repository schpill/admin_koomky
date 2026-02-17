<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignAttachmentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'filename',
        'path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @return BelongsTo<Campaign, CampaignAttachment>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
