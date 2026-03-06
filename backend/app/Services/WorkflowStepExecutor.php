<?php

namespace App\Services;

use App\Jobs\SendWorkflowEmailJob;
use App\Models\Contact;
use App\Models\ContactScoreEvent;
use App\Models\DripSequence;
use App\Models\Tag;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class WorkflowStepExecutor
{
    /** @var list<string> */
    private const UPDATABLE_CONTACT_FIELDS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
    ];

    public function __construct(
        private readonly DripEnrollmentService $dripEnrollmentService,
        private readonly WorkflowTriggerService $workflowTriggerService,
    ) {}

    public function execute(WorkflowStep $step, WorkflowEnrollment $enrollment): ?string
    {
        $contact = $enrollment->contact;
        if (! $contact instanceof Contact) {
            throw new RuntimeException('Workflow enrollment contact is missing.');
        }

        /** @var array<string, mixed> $config */
        $config = (array) $step->config;

        return match ($step->type) {
            'send_email' => $this->dispatchSendEmail($step, $enrollment),
            'wait' => $this->executeWait($step, $enrollment, $config),
            'condition' => $this->executeCondition($step, $contact, $config),
            'update_score' => $this->updateScore($step, $contact, $config),
            'add_tag' => $this->attachTag($step, $contact, (string) ($config['tag'] ?? '')),
            'remove_tag' => $this->detachTag($step, $contact, (string) ($config['tag'] ?? '')),
            'enroll_drip' => $this->enrollInDrip($step, $contact, $config),
            'update_field' => $this->updateField($step, $contact, $config),
            'end' => null,
        };
    }

    private function dispatchSendEmail(WorkflowStep $step, WorkflowEnrollment $enrollment): ?string
    {
        SendWorkflowEmailJob::dispatch($enrollment->id, $step->id);

        return $step->next_step_id;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function executeWait(WorkflowStep $step, WorkflowEnrollment $enrollment, array $config): ?string
    {
        $duration = max(0, (int) ($config['duration'] ?? 0));
        $unit = (string) ($config['unit'] ?? 'hours');
        $referenceTime = Carbon::parse((string) ($enrollment->last_processed_at ?? $enrollment->enrolled_at));
        $readyAt = $unit === 'days'
            ? $referenceTime->copy()->addDays($duration)
            : $referenceTime->copy()->addHours($duration);

        return $readyAt->isFuture() ? $step->id : $step->next_step_id;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function executeCondition(WorkflowStep $step, Contact $contact, array $config): ?string
    {
        $attribute = (string) ($config['attribute'] ?? '');
        $operator = (string) ($config['operator'] ?? 'eq');
        $expected = $config['value'] ?? null;
        $actual = data_get($contact, $attribute);

        $matches = match ($operator) {
            'eq' => $actual == $expected,
            'neq' => $actual != $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            default => false,
        };

        return $matches ? $step->next_step_id : $step->else_step_id;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function updateScore(WorkflowStep $step, Contact $contact, array $config): ?string
    {
        $delta = (int) ($config['delta'] ?? 0);
        $previousScore = (int) $contact->email_score;

        ContactScoreEvent::query()->create([
            'user_id' => $contact->client?->user_id,
            'contact_id' => $contact->id,
            'event' => 'workflow_score_adjustment',
            'points' => $delta,
            'source_campaign_id' => null,
            'expires_at' => null,
            'created_at' => now(),
        ]);

        $contact->forceFill([
            'email_score' => $previousScore + $delta,
            'email_score_updated_at' => now(),
        ])->save();

        $freshContact = $contact->fresh();
        if ($freshContact instanceof Contact) {
            $this->workflowTriggerService->evaluateTriggers('score_threshold', $freshContact, [
                'previous_score' => $previousScore,
                'score' => (int) $freshContact->email_score,
            ]);
        }

        return $step->next_step_id;
    }

    private function attachTag(WorkflowStep $step, Contact $contact, string $tagName): ?string
    {
        $client = $contact->client;
        if ($client === null || $tagName === '') {
            return $step->next_step_id;
        }

        $tag = Tag::query()->firstOrCreate(
            ['user_id' => $client->user_id, 'name' => $tagName],
            ['color' => '#2563eb']
        );

        $client->tags()->syncWithoutDetaching([$tag->id]);

        return $step->next_step_id;
    }

    private function detachTag(WorkflowStep $step, Contact $contact, string $tagName): ?string
    {
        $client = $contact->client;
        if ($client === null || $tagName === '') {
            return $step->next_step_id;
        }

        $tag = Tag::query()
            ->where('user_id', $client->user_id)
            ->where('name', $tagName)
            ->first();

        if ($tag instanceof Tag) {
            $client->tags()->detach($tag->id);
        }

        return $step->next_step_id;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function enrollInDrip(WorkflowStep $step, Contact $contact, array $config): ?string
    {
        $sequenceId = $config['sequence_id'] ?? null;
        $sequence = is_string($sequenceId) ? DripSequence::query()->find($sequenceId) : null;

        if ($sequence instanceof DripSequence) {
            $this->dripEnrollmentService->enroll($contact, $sequence);
        }

        return $step->next_step_id;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function updateField(WorkflowStep $step, Contact $contact, array $config): ?string
    {
        $field = (string) ($config['field'] ?? '');
        if (! in_array($field, self::UPDATABLE_CONTACT_FIELDS, true)) {
            throw new InvalidArgumentException("Workflow field [{$field}] is not allowed.");
        }

        $contact->update([
            $field => $config['value'] ?? null,
        ]);

        return $step->next_step_id;
    }
}
