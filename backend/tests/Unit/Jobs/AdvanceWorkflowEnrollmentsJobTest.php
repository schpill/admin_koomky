<?php

use App\Jobs\AdvanceWorkflowEnrollmentsJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('advance workflow enrollments job moves enrollment to next step', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $entry = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'condition',
        'config' => ['attribute' => 'email_score', 'operator' => 'gte', 'value' => 0],
    ]);
    $next = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $entry->update(['next_step_id' => $next->id]);
    $workflow->update(['entry_step_id' => $entry->id]);
    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $entry->id,
        'last_processed_at' => now()->subHours(2),
    ]);

    app(AdvanceWorkflowEnrollmentsJob::class)->handle(app(\App\Services\WorkflowStepExecutor::class));

    expect($enrollment->fresh()->current_step_id)->toBe($next->id);
});

test('advance workflow enrollments job completes enrollment on end step', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $entry = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $entry->id]);
    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $entry->id,
    ]);

    app(AdvanceWorkflowEnrollmentsJob::class)->handle(app(\App\Services\WorkflowStepExecutor::class));

    expect($enrollment->fresh()->status)->toBe('completed');
});

test('advance workflow enrollments job isolates enrollment failures', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);

    $badStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'update_field',
        'config' => ['field' => 'not_allowed', 'value' => 'x'],
    ]);
    $goodStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);

    $failedEnrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $badStep->id,
    ]);
    $goodEnrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'current_step_id' => $goodStep->id,
    ]);

    app(AdvanceWorkflowEnrollmentsJob::class)->handle(app(\App\Services\WorkflowStepExecutor::class));

    expect($failedEnrollment->fresh()->status)->toBe('failed');
    expect($goodEnrollment->fresh()->status)->toBe('completed');
});
