<?php

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('email campaign throttling dispatches 100 recipients within one-minute window', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Contact::factory()
        ->count(100)
        ->create([
            'client_id' => $client->id,
            'email_unsubscribed_at' => null,
        ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
        'status' => 'draft',
        'settings' => ['throttle_rate_per_minute' => 100],
    ]);

    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])
        ->handle(app(SegmentFilterEngine::class));

    $jobs = Queue::pushed(SendCampaignEmailJob::class);

    expect($jobs)->toHaveCount(100);

    $delays = $jobs
        ->map(fn (SendCampaignEmailJob $job): int => toDelaySeconds($job->delay))
        ->values();

    expect($delays->first())->toBe(0);
    expect($delays->max())->toBeLessThanOrEqual(60);
    expect($delays->sort()->values()->all())->toBe($delays->all());
});

test('invalid throttle setting falls back to 100 emails per minute', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Contact::factory()
        ->count(3)
        ->create([
            'client_id' => $client->id,
            'email_unsubscribed_at' => null,
        ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
        'status' => 'draft',
        'settings' => ['throttle_rate_per_minute' => 'not-a-number'],
    ]);

    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])
        ->handle(app(SegmentFilterEngine::class));

    $jobs = Queue::pushed(SendCampaignEmailJob::class);

    expect($jobs)->toHaveCount(3);

    $delays = $jobs
        ->map(fn (SendCampaignEmailJob $job): int => toDelaySeconds($job->delay))
        ->values();

    expect($delays->max())->toBeLessThanOrEqual(2);
});

function toDelaySeconds(mixed $delay): int
{
    if ($delay instanceof \DateTimeInterface) {
        return now()->diffInSeconds(Carbon::instance($delay), false);
    }

    if (is_numeric($delay)) {
        return (int) $delay;
    }

    return 0;
}
