<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(Model $subject, string $description, ?array $metadata = null): Activity
    {
        /** @var string|null $userId */
        $userId = auth()->id() ?? $subject->getAttribute('user_id') ?? ($subject->getAttribute('client') ? $subject->getAttribute('client')->user_id : null);

        return Activity::create([
            'user_id' => $userId,
            'subject_id' => $subject->getKey(),
            'subject_type' => get_class($subject),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
