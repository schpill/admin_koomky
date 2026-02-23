<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    /** @use HasFactory<\Database\Factories\TicketMessageFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
        'is_internal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_internal' => 'boolean',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include public messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TicketMessage>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TicketMessage>
     */
    public function scopeIsPublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope a query to only include internal messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TicketMessage>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TicketMessage>
     */
    public function scopeIsInternal($query)
    {
        return $query->where('is_internal', true);
    }
}
