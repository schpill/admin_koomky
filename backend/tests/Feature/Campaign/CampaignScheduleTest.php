<?php

use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('scheduled campaigns are dispatched by scheduler command', function () {
    Queue::fake();

    $user = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'scheduled',
        'scheduled_at' => now()->subMinute(),
    ]);

    Artisan::call('campaigns:dispatch-scheduled');

    Queue::assertPushed(SendEmailCampaignJob::class, fn (SendEmailCampaignJob $job) => $job->campaignId === $campaign->id);
});

test('paused campaign is not dispatched by scheduler', function () {
    Queue::fake();

    $user = User::factory()->create();

    Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'paused',
        'scheduled_at' => now()->subMinute(),
    ]);

    Artisan::call('campaigns:dispatch-scheduled');

    Queue::assertNotPushed(SendEmailCampaignJob::class);
});
