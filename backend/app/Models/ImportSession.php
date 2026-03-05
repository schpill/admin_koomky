<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $filename
 * @property string $original_filename
 * @property string $status
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $success_rows
 * @property int $error_rows
 * @property array<string, string|null>|null $column_mapping
 * @property array<int, string>|null $default_tags
 * @property array<string, string>|null $options
 * @property string|null $error_summary
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImportSession extends Model
{
    /** @use HasFactory<\Database\Factories\ImportSessionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'status',
        'total_rows',
        'processed_rows',
        'success_rows',
        'error_rows',
        'column_mapping',
        'default_tags',
        'options',
        'error_summary',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'default_tags' => 'array',
            'options' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, ImportSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ImportSessionError, ImportSession>
     */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportSessionError::class, 'session_id');
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function progressPercent(): int
    {
        if ($this->total_rows <= 0) {
            return 0;
        }

        return (int) min(100, floor(($this->processed_rows / $this->total_rows) * 100));
    }
}
