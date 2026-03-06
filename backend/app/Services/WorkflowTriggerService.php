<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use App\Models\Workflow;

class WorkflowTriggerService
{
    public function __construct(private readonly WorkflowEnrollmentService $workflowEnrollmentService) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function evaluateTriggers(string $event, Contact $contact, array $context = []): void
    {
        $user = $contact->client?->user;
        if ($user === null) {
            return;
        }

        $workflows = Workflow::query()
            ->forUser($user)
            ->active()
            ->withTrigger($event)
            ->get();

        foreach ($workflows as $workflow) {
            $triggerConfig = $workflow->trigger_config;
            $config = is_array($triggerConfig) ? $triggerConfig : [];
            if (! $this->matchesTrigger($event, $config, $contact, $context)) {
                continue;
            }

            $this->workflowEnrollmentService->enroll($contact, $workflow);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private function matchesTrigger(string $event, array $config, Contact $contact, array $context): bool
    {
        return match ($event) {
            'manual', 'contact_created' => true,
            'email_opened', 'email_clicked' => ! isset($config['campaign_id']) || $config['campaign_id'] === ($context['campaign_id'] ?? null),
            'score_threshold' => $this->matchesScoreThreshold($config, $context),
            'segment_entered' => $this->matchesSegment($config, $contact),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private function matchesScoreThreshold(array $config, array $context): bool
    {
        $threshold = (int) ($config['threshold'] ?? 0);
        $score = (int) ($context['score'] ?? 0);
        $previousScore = (int) ($context['previous_score'] ?? $score);

        return $score >= $threshold
            && ($previousScore < $threshold || $previousScore === $score);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function matchesSegment(array $config, Contact $contact): bool
    {
        $segmentId = $config['segment_id'] ?? null;
        if (! is_string($segmentId)) {
            return false;
        }

        $segment = Segment::query()->find($segmentId);
        if (! $segment instanceof Segment || $segment->user === null) {
            return false;
        }

        $contactIds = app(SegmentFilterEngine::class)
            ->apply($segment->user, (array) $segment->filters)
            ->pluck('id')
            ->all();

        return in_array($contact->id, $contactIds, true);
    }
}
