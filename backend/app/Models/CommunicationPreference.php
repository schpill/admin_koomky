<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\CommunicationPreferenceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'contact_id',
        'category',
        'subscribed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscribed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Contact, CommunicationPreference>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<User, CommunicationPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
