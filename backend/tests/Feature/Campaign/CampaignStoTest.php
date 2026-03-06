<?php

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('campaign store accepts sto fields', function () {
    $user = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id]);
    $segment = \App\Models\Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => $client->city],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/campaigns', [
        'name' => 'STO Campaign',
        'type' => 'email',
        'segment_id' => $segment->id,
        'subject' => 'Subject',
        'content' => 'Body',
        'use_sto' => true,
        'sto_window_hours' => 12,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.use_sto', true)
        ->assertJsonPath('data.sto_window_hours', 12);
});

test('send email campaign job delays recipients when sto is enabled and history exists', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'sto@example.test',
        'email_unsubscribed_at' => null,
    ]);

    $historyCampaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    foreach ([9, 9, 9] as $hour) {
        CampaignRecipient::factory()->create([
            'campaign_id' => $historyCampaign->id,
            'contact_id' => $contact->id,
            'opened_at' => now()->startOfDay()->addHours($hour),
        ]);
    }

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
        'use_sto' => true,
        'sto_window_hours' => 24,
        'settings' => ['throttle_rate_per_minute' => 100],
    ]);

    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])
        ->handle(app(SegmentFilterEngine::class));

    $jobs = Queue::pushed(SendCampaignEmailJob::class);
    expect($jobs)->toHaveCount(1);

    $delay = $jobs->map(fn (SendCampaignEmailJob $job): int => stoDelaySeconds($job->delay))->first();
    expect($delay)->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(24 * 3600);
});

function stoDelaySeconds(mixed $delay): int
{
    if ($delay instanceof \DateTimeInterface) {
        return now()->diffInSeconds(Carbon::instance($delay), false);
    }

    if (is_numeric($delay)) {
        return (int) $delay;
    }

    return 0;
}
