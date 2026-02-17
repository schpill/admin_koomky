<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $client_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $position
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $email_unsubscribed_at
 * @property \Illuminate\Support\Carbon|null $sms_opted_out_at
 * @property bool $email_consent
 * @property \Illuminate\Support\Carbon|null $email_consent_date
 * @property bool $sms_consent
 * @property \Illuminate\Support\Carbon|null $sms_consent_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'client_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'is_primary',
        'email_unsubscribed_at',
        'sms_opted_out_at',
        'email_consent',
        'email_consent_date',
        'sms_consent',
        'sms_consent_date',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_primary' => 'boolean',
        'email_unsubscribed_at' => 'datetime',
        'sms_opted_out_at' => 'datetime',
        'email_consent' => 'boolean',
        'email_consent_date' => 'datetime',
        'sms_consent' => 'boolean',
        'sms_consent_date' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Client, \App\Models\Contact>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    public function scopeEmailSubscribed(Builder $query): Builder
    {
        return $query->whereNull('email_unsubscribed_at');
    }

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    public function scopeSmsOptedIn(Builder $query): Builder
    {
        return $query->whereNull('sms_opted_out_at');
    }
}
