<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $task_id
 * @property string $filename
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 */
class TaskAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\TaskAttachmentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'task_id',
        'filename',
        'path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @return BelongsTo<Task, TaskAttachment>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
