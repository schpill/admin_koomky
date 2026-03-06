<?php

use App\Jobs\SendDripStepEmailJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\SuppressedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('send drip step job creates campaign recipient for active contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'active@drip.test',
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    $step = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
        'subject' => 'Active',
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

    $this->assertDatabaseHas('campaign_recipients', [
        'contact_id' => $contact->id,
        'email' => 'active@drip.test',
    ]);
});

test('send drip step job skips suppressed emails', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'blocked@drip.test',
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    $step = DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
    ]);
    $enrollment = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
        'current_step_position' => 0,
        'status' => 'active',
    ]);

    SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => 'blocked@drip.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
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
    expect($enrollment->refresh()->status)->toBe('cancelled');
});
