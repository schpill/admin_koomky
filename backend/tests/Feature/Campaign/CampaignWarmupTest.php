<?php

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Contact;
use App\Models\EmailWarmupPlan;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('email campaign send respects active warmup quota and requeues remaining recipients', function () {
    Queue::fake();

    $user = User::factory()->create([
        'warmup_sent_today' => 0,
        'warmup_last_reset_at' => now()->toDateString(),
    ]);
    $client = Client::query()->create([
        'user_id' => $user->id,
        'reference' => 'CLI-2026-1003',
        'name' => 'Warmup Client',
        'email' => 'client@example.test',
        'phone' => '+33102030405',
        'address' => '1 rue Example',
        'city' => 'Paris',
        'zip_code' => '75001',
        'country' => 'France',
        'industry' => 'Wedding Planner',
        'department' => '75',
        'status' => 'active',
    ]);

    Contact::factory()->for($client)->count(3)->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'draft',
        'segment_id' => null,
    ]);

    EmailWarmupPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'current_daily_limit' => 2,
        'daily_volume_start' => 2,
    ]);

    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])
        ->handle(app(SegmentFilterEngine::class));

    Queue::assertPushed(SendCampaignEmailJob::class, 2);
    Queue::assertPushed(SendEmailCampaignJob::class, 1);

    expect($user->fresh()->warmup_sent_today)->toBe(2)
        ->and($campaign->fresh()->status)->toBe('scheduled');
});
