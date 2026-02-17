<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityService;
use App\Services\Calendar\CalendarAutoEventService;

class ProjectObserver
{
    public function created(Project $project): void
    {
        app(CalendarAutoEventService::class)->syncProjectDeadline($project);

        $client = $project->client;
        if (! $client) {
            return;
        }

        ActivityService::log($client, "Project created: {$project->name}", [
            'project_id' => $project->id,
            'project_reference' => $project->reference,
            'status' => $project->status,
        ]);
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged(['deadline', 'name'])) {
            app(CalendarAutoEventService::class)->syncProjectDeadline($project);
        }

        if (! $project->wasChanged('status')) {
            return;
        }

        $client = $project->client;
        if (! $client) {
            return;
        }

        $previousStatus = $project->getOriginal('status');
        $newStatus = $project->status;

        ActivityService::log($client, "Project status changed: {$project->name}", [
            'project_id' => $project->id,
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);

        if ($newStatus === 'completed') {
            ActivityService::log($client, "Project completed: {$project->name}", [
                'project_id' => $project->id,
                'project_reference' => $project->reference,
            ]);
        }
    }
}
