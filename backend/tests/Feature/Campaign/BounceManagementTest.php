<?php

use App\Jobs\RetryBouncedEmailJob;
use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('hard bounce creates suppression entry', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
        'email' => 'hard@example.test',
    ]);

    $this->postJson('/webhooks/email', [
        'event' => 'bounce',
        'recipient_id' => $recipient->id,
        'bounce_type' => 'hard',
    ])->assertOk();

    $this->assertDatabaseHas('suppressed_emails', [
        'user_id' => $user->id,
        'email' => 'hard@example.test',
        'reason' => 'hard_bounce',
    ]);
});

test('soft bounce retries and escalates to hard bounce after three attempts', function () {
    Queue::fake();

    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
        'email' => 'soft@example.test',
    ]);

    $this->postJson('/webhooks/email', [
        'event' => 'bounce',
        'recipient_id' => $recipient->id,
        'bounce_type' => 'soft',
    ])->assertOk();

    Queue::assertPushed(RetryBouncedEmailJob::class, fn (RetryBouncedEmailJob $job) => $job->recipientId === $recipient->id);

    $recipient->refresh()->update([
        'bounce_count' => 2,
        'bounce_type' => 'soft',
        'status' => 'sent',
    ]);

    (new RetryBouncedEmailJob($recipient->id))->handle();

    expect($recipient->refresh()->bounce_type)->toBe('hard');
    expect($recipient->refresh()->status)->toBe('bounced');

    $this->assertDatabaseHas('suppressed_emails', [
        'user_id' => $user->id,
        'email' => 'soft@example.test',
        'reason' => 'hard_bounce',
    ]);
});

test('campaign orchestration skips suppressed emails', function () {
    Queue::fake();

    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $clientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);

    Contact::factory()->create(['client_id' => $clientA->id, 'email' => 'ok@example.test']);
    Contact::factory()->create(['client_id' => $clientB->id, 'email' => 'blocked@example.test']);

    \App\Models\SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => 'blocked@example.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Paris'],
                    ],
                ],
            ],
        ],
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => $segment->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Hello',
        'content' => 'Hi',
    ]);

    (new SendEmailCampaignJob($campaign->id))->handle(app(SegmentFilterEngine::class));

    $this->assertDatabaseHas('campaign_recipients', [
        'campaign_id' => $campaign->id,
        'email' => 'ok@example.test',
    ]);

    $this->assertDatabaseMissing('campaign_recipients', [
        'campaign_id' => $campaign->id,
        'email' => 'blocked@example.test',
    ]);

    Queue::assertPushed(SendCampaignEmailJob::class, 1);
});
