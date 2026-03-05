<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $session_id
 * @property int $row_number
 * @property array<string, mixed> $raw_data
 * @property string $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImportSessionError extends Model
{
    /** @use HasFactory<\Database\Factories\ImportSessionErrorFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'row_number',
        'raw_data',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ImportSession, ImportSessionError>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ImportSession::class, 'session_id');
    }
}
