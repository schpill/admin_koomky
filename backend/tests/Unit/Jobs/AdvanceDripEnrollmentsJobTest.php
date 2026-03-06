<?php

use App\Jobs\AdvanceDripEnrollmentsJob;
use App\Jobs\SendDripStepEmailJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('advance drip enrollments dispatches due first step', function () {
    Queue::fake();

    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
        'delay_hours' => 0,
        'condition' => 'none',
    ]);

    DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
        'current_step_position' => 0,
        'status' => 'active',
        'enrolled_at' => now()->subHour(),
        'last_processed_at' => null,
    ]);

    (new AdvanceDripEnrollmentsJob)->handle();

    Queue::assertPushed(SendDripStepEmailJob::class, 1);
});

test('advance drip enrollments honors if_opened and if_not_opened conditions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);

    DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
        'delay_hours' => 0,
        'condition' => 'none',
    ]);
    $openedStep = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 2,
        'delay_hours' => 1,
        'condition' => 'if_opened',
    ]);
    $notOpenedStep = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 3,
        'delay_hours' => 1,
        'condition' => 'if_not_opened',
    ]);

    $enrollment = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
        'current_step_position' => 1,
        'status' => 'active',
        'last_processed_at' => now()->subHours(2),
    ]);

    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);
    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'email' => $contact->email,
        'status' => 'opened',
        'opened_at' => now()->subHour(),
        'metadata' => [
            'drip_enrollment_id' => $enrollment->id,
            'drip_step_position' => 1,
        ],
    ]);

    (new AdvanceDripEnrollmentsJob)->handle();

    Queue::assertPushed(SendDripStepEmailJob::class, fn (SendDripStepEmailJob $job) => $job->stepId === $openedStep->id);
    Queue::assertNotPushed(SendDripStepEmailJob::class, fn (SendDripStepEmailJob $job) => $job->stepId === $notOpenedStep->id);
});

test('send drip step job completes enrollment after final step', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'final@drip.test',
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    $step = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
        'delay_hours' => 0,
        'condition' => 'none',
        'subject' => 'Welcome',
        'content' => '<p>Hello {{ first_name }}</p>',
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

    expect($enrollment->refresh()->status)->toBe('completed');
    expect($enrollment->refresh()->current_step_position)->toBe(1);
    $this->assertDatabaseHas('campaign_recipients', [
        'contact_id' => $contact->id,
        'email' => 'final@drip.test',
    ]);
});
