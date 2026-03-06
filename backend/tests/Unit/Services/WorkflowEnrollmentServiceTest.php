<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\SuppressedEmail;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('workflow enrollment service creates an enrollment', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $entryStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $entryStep->id]);

    $enrollment = app(WorkflowEnrollmentService::class)->enroll($contact, $workflow->fresh());

    expect($enrollment)->not->toBeNull();
    expect($enrollment?->workflow_id)->toBe($workflow->id);
    expect($enrollment?->current_step_id)->toBe($entryStep->id);
    expect($enrollment?->status)->toBe('active');
});

test('workflow enrollment service rejects duplicate active enrollment', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $entryStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $entryStep->id]);

    $service = app(WorkflowEnrollmentService::class);

    $first = $service->enroll($contact, $workflow->fresh());
    $second = $service->enroll($contact, $workflow->fresh());

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect($workflow->enrollments()->count())->toBe(1);
});

test('workflow enrollment service skips suppressed contacts', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'blocked@workflow.test',
    ]);
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $entryStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $entryStep->id]);

    SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => $contact->email,
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $enrollment = app(WorkflowEnrollmentService::class)->enroll($contact, $workflow->fresh());

    expect($enrollment)->toBeNull();
    expect($workflow->enrollments()->count())->toBe(0);
});

test('workflow enrollment service enrolls all contacts from a segment', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Lyon'],
                    ],
                ],
            ],
        ],
    ]);
    $workflow = Workflow::factory()->create([
        'user_id' => $user->id,
        'trigger_type' => 'segment_entered',
        'trigger_config' => ['segment_id' => $segment->id],
    ]);
    $entryStep = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $entryStep->id]);

    $clientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);
    $clientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);
    Contact::factory()->create(['client_id' => $clientA->id]);
    Contact::factory()->create(['client_id' => $clientB->id]);

    $count = app(WorkflowEnrollmentService::class)->enrollSegment($segment, $workflow->fresh());

    expect($count)->toBe(2);
    expect($workflow->fresh()->enrollments()->count())->toBe(2);
});
