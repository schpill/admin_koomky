<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property bool $portal_enabled
 * @property string|null $custom_logo
 * @property string|null $custom_color
 * @property string|null $welcome_message
 * @property bool $payment_enabled
 * @property bool $quote_acceptance_enabled
 * @property string|null $stripe_publishable_key
 * @property string|null $stripe_secret_key
 * @property string|null $stripe_webhook_secret
 * @property array<int, string>|null $payment_methods_enabled
 */
class PortalSettings extends Model
{
    /** @use HasFactory<\Database\Factories\PortalSettingsFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'portal_enabled',
        'custom_logo',
        'custom_color',
        'welcome_message',
        'payment_enabled',
        'quote_acceptance_enabled',
        'stripe_publishable_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'payment_methods_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'portal_enabled' => 'boolean',
            'payment_enabled' => 'boolean',
            'quote_acceptance_enabled' => 'boolean',
            'stripe_publishable_key' => 'encrypted',
            'stripe_secret_key' => 'encrypted',
            'stripe_webhook_secret' => 'encrypted',
            'payment_methods_enabled' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, PortalSettings>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
