<?php

use App\Jobs\SendDripStepEmailJob;
use App\Jobs\SendWorkflowEmailJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('drip email job skips contact blocked for promotional emails', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'blocked@drip.test',
    ]);
    app(\App\Services\PreferenceCenterService::class)->updatePreference($contact, 'promotional', false);

    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    $step = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
        'subject' => 'Promo drip',
        'content' => '<p>Hello</p>',
    ]);
    $enrollment = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
        'current_step_position' => 0,
        'status' => 'active',
    ]);

    (new SendDripStepEmailJob($enrollment->id, $step->id))->handle(
        app(\App\Services\PersonalizationService::class),
        app(\App\Services\EmailTrackingTokenService::class),
        app(\App\Services\MailConfigService::class)
    );

    $this->assertDatabaseMissing('campaign_recipients', [
        'contact_id' => $contact->id,
        'email' => 'blocked@drip.test',
    ]);
});

test('workflow email job skips contact blocked for promotional emails', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'blocked@workflow.test',
    ]);
    app(\App\Services\PreferenceCenterService::class)->updatePreference($contact, 'promotional', false);

    $workflow = Workflow::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $step = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'config' => ['subject' => 'Workflow', 'content' => '<p>Hello</p>'],
    ]);
    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => $contact->id,
        'current_step_id' => $step->id,
        'status' => 'active',
    ]);

    (new SendWorkflowEmailJob($enrollment->id, $step->id))->handle(
        app(\App\Services\PersonalizationService::class),
        app(\App\Services\EmailTrackingTokenService::class),
        app(\App\Services\MailConfigService::class),
        app(\App\Services\PreferenceCenterService::class),
        app(\App\Services\SuppressionService::class),
    );

    $this->assertDatabaseMissing('campaign_recipients', [
        'contact_id' => $contact->id,
        'email' => 'blocked@workflow.test',
    ]);
});
