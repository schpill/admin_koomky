<?php

use App\Jobs\SendWorkflowEmailJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use App\Services\WorkflowStepExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeWorkflowEnrollmentForExecutor(array $stepConfig = [], string $type = 'end'): array
{
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id, 'email_score' => 20]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $step = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => $type,
        'config' => $stepConfig,
    ]);
    $workflow->update(['entry_step_id' => $step->id]);
    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $step->id,
    ]);

    return [$user, $client, $contact, $workflow, $step, $enrollment];
}

test('workflow step executor dispatches send email job', function () {
    Queue::fake();

    [, , , , $step, $enrollment] = makeWorkflowEnrollmentForExecutor([
        'subject' => 'Hi',
        'content' => 'Body',
    ], 'send_email');

    app(WorkflowStepExecutor::class)->execute($step, $enrollment);

    Queue::assertPushed(SendWorkflowEmailJob::class, fn (SendWorkflowEmailJob $job) => $job->enrollmentId === $enrollment->id);
});

test('workflow step executor blocks wait step until due time', function () {
    [, , , , $step, $enrollment] = makeWorkflowEnrollmentForExecutor([
        'duration' => 2,
        'unit' => 'hours',
    ], 'wait');

    $enrollment->update(['last_processed_at' => now()]);

    $nextStepId = app(WorkflowStepExecutor::class)->execute($step, $enrollment->fresh());

    expect($nextStepId)->toBe($step->id);
});

test('workflow step executor evaluates condition and updates score and tags', function () {
    [, $client, $contact, , $conditionStep, $enrollment] = makeWorkflowEnrollmentForExecutor([
        'attribute' => 'email_score',
        'operator' => 'gte',
        'value' => 10,
    ], 'condition');

    $trueStep = WorkflowStep::factory()->create([
        'workflow_id' => $conditionStep->workflow_id,
        'type' => 'update_score',
        'config' => ['delta' => 15],
    ]);
    $conditionStep->update(['next_step_id' => $trueStep->id]);

    $result = app(WorkflowStepExecutor::class)->execute($conditionStep->fresh(), $enrollment->fresh());
    expect($result)->toBe($trueStep->id);

    app(WorkflowStepExecutor::class)->execute($trueStep->fresh(), $enrollment->fresh());
    expect($contact->fresh()->email_score)->toBe(35);

    $tagStep = WorkflowStep::factory()->create([
        'workflow_id' => $conditionStep->workflow_id,
        'type' => 'add_tag',
        'config' => ['tag' => 'Hot'],
    ]);
    app(WorkflowStepExecutor::class)->execute($tagStep, $enrollment->fresh());

    $tag = Tag::query()->where('user_id', $client->user_id)->where('name', 'Hot')->first();
    expect($tag)->not->toBeNull();
    expect($client->fresh()->tags->pluck('id')->all())->toContain($tag?->id);
});
