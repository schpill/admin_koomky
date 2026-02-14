<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    public static function log(Model $subject, string $description, ?array $metadata = null): Activity
    {
        return Activity::create([
            'user_id' => auth()->id() ?? $subject->user_id ?? $subject->client?->user_id,
            'subject_id' => $subject->id,
            'subject_type' => get_class($subject),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
