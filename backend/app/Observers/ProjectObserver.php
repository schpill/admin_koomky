<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityService;
use App\Services\Calendar\CalendarAutoEventService;
use App\Services\WebhookDispatchService;

class ProjectObserver
{
    public function created(Project $project): void
    {
        app(CalendarAutoEventService::class)->syncProjectDeadline($project);

        $client = $project->client;
        if ($client) {
            ActivityService::log($client, "Project created: {$project->name}", [
                'project_id' => $project->id,
                'project_reference' => $project->reference,
                'status' => $project->status,
            ]);
        }
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
        $previousStatus = (string) $project->getOriginal('status');
        $newStatus = (string) $project->status;

        if ($client) {
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

        // Dispatch status-specific webhook
        $webhookEvent = match ($newStatus) {
            'completed' => 'project.completed',
            'cancelled' => 'project.cancelled',
            default => null,
        };

        if ($webhookEvent !== null) {
            $this->dispatchWebhook($project, $webhookEvent, [
                'previous_status' => $previousStatus,
            ]);
        }
    }

    /**
     * Dispatch a webhook for the project event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Project $project, string $event, array $extraData = []): void
    {
        $userId = $project->user_id;

        $data = array_merge([
            'id' => $project->id,
            'reference' => $project->reference,
            'name' => $project->name,
            'status' => $project->status,
            'client_id' => $project->client_id,
            'deadline' => $project->deadline?->toDateString(),
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }
}
