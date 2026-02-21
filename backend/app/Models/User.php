<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $business_name
 * @property string|null $avatar_path
 * @property int $payment_terms_days
 * @property string|null $bank_details
 * @property string|null $invoice_footer
 * @property string $invoice_numbering_pattern
 * @property string $base_currency
 * @property string $exchange_rate_provider
 * @property string $accounting_journal_sales
 * @property string $accounting_journal_purchases
 * @property string $accounting_journal_bank
 * @property string|null $accounting_auxiliary_prefix
 * @property int $fiscal_year_start_month
 * @property array<string, mixed>|null $email_settings
 * @property array<string, mixed>|null $sms_settings
 * @property array<string, mixed>|null $notification_preferences
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property \Illuminate\Support\Carbon|null $deletion_scheduled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'business_name',
        'avatar_path',
        'payment_terms_days',
        'bank_details',
        'invoice_footer',
        'invoice_numbering_pattern',
        'base_currency',
        'exchange_rate_provider',
        'accounting_journal_sales',
        'accounting_journal_purchases',
        'accounting_journal_bank',
        'accounting_auxiliary_prefix',
        'fiscal_year_start_month',
        'email_settings',
        'sms_settings',
        'notification_preferences',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'deletion_scheduled_at',
        'document_storage_quota_mb',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'payment_terms_days' => 'integer',
            'bank_details' => 'encrypted',
            'base_currency' => 'string',
            'exchange_rate_provider' => 'string',
            'accounting_journal_sales' => 'string',
            'accounting_journal_purchases' => 'string',
            'accounting_journal_bank' => 'string',
            'accounting_auxiliary_prefix' => 'string',
            'fiscal_year_start_month' => 'integer',
            'email_settings' => 'array',
            'sms_settings' => 'array',
            'notification_preferences' => 'array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'deletion_scheduled_at' => 'datetime',
            'document_storage_quota_mb' => 'integer',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Client, \App\Models\User>
     */
    public function clients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Tag, \App\Models\User>
     */
    public function tags(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Activity, \App\Models\User>
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Project, \App\Models\User>
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TimeEntry, \App\Models\User>
     */
    public function timeEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Invoice, \App\Models\User>
     */
    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Quote, \App\Models\User>
     */
    public function quotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Campaign, \App\Models\User>
     */
    public function campaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CampaignTemplate, \App\Models\User>
     */
    public function campaignTemplates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignTemplate::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Segment, \App\Models\User>
     */
    public function segments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Segment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CreditNote, \App\Models\User>
     */
    public function creditNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\RecurringInvoiceProfile, \App\Models\User>
     */
    public function recurringInvoiceProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecurringInvoiceProfile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CalendarConnection, \App\Models\User>
     */
    public function calendarConnections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarConnection::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CalendarEvent, \App\Models\User>
     */
    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\PortalSettings, \App\Models\User>
     */
    public function portalSettings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PortalSettings::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PortalAccessToken, \App\Models\User>
     */
    public function createdPortalAccessTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PortalAccessToken::class, 'created_by_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ExpenseCategory, \App\Models\User>
     */
    public function expenseCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Expense, \App\Models\User>
     */
    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\WebhookEndpoint, \App\Models\User>
     */
    public function webhookEndpoints(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Lead, \App\Models\User>
     */
    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Document, \App\Models\User>
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Document::class);
    }
}
