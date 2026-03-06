<?php

use App\Jobs\AdvanceWorkflowEnrollmentsJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('workflow executes full branch send wait condition send end', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 30,
    ]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $sendA = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'config' => ['subject' => 'A', 'content' => 'Body A'],
    ]);
    $wait = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'wait',
        'config' => ['duration' => 0, 'unit' => 'hours'],
    ]);
    $condition = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'condition',
        'config' => ['attribute' => 'email_score', 'operator' => 'gte', 'value' => 20],
    ]);
    $sendB = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'config' => ['subject' => 'B', 'content' => 'Body B'],
    ]);
    $end = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);

    $sendA->update(['next_step_id' => $wait->id]);
    $wait->update(['next_step_id' => $condition->id]);
    $condition->update(['next_step_id' => $sendB->id, 'else_step_id' => $end->id]);
    $sendB->update(['next_step_id' => $end->id]);
    $workflow->update(['entry_step_id' => $sendA->id]);

    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $sendA->id,
        'last_processed_at' => now()->subHour(),
    ]);

    $job = app(AdvanceWorkflowEnrollmentsJob::class);

    $job->handle(app(\App\Services\WorkflowStepExecutor::class));
    expect($enrollment->fresh()->current_step_id)->toBe($wait->id);

    $job->handle(app(\App\Services\WorkflowStepExecutor::class));
    expect($enrollment->fresh()->current_step_id)->toBe($condition->id);

    $job->handle(app(\App\Services\WorkflowStepExecutor::class));
    expect($enrollment->fresh()->current_step_id)->toBe($sendB->id);

    $job->handle(app(\App\Services\WorkflowStepExecutor::class));
    expect($enrollment->fresh()->current_step_id)->toBe($end->id);

    $job->handle(app(\App\Services\WorkflowStepExecutor::class));
    expect($enrollment->fresh()->status)->toBe('completed');
});

test('workflow execution follows else branch when condition is false', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 5,
    ]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $condition = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'condition',
        'config' => ['attribute' => 'email_score', 'operator' => 'gte', 'value' => 20],
    ]);
    $send = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'config' => ['subject' => 'true', 'content' => 'true'],
    ]);
    $end = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $condition->update(['next_step_id' => $send->id, 'else_step_id' => $end->id]);
    $workflow->update(['entry_step_id' => $condition->id]);

    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $condition->id,
    ]);

    app(AdvanceWorkflowEnrollmentsJob::class)->handle(app(\App\Services\WorkflowStepExecutor::class));

    expect($enrollment->fresh()->current_step_id)->toBe($end->id);
});
