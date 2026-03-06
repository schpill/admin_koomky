<?php

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('send email campaign deduplicates recipients with same email and dispatches one send job', function () {
    Queue::fake();

    $user = User::factory()->create();

    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    Contact::factory()->create([
        'client_id' => $clientA->id,
        'email' => 'duplicate@example.test',
        'email_unsubscribed_at' => null,
    ]);

    Contact::factory()->create([
        'client_id' => $clientB->id,
        'email' => 'duplicate@example.test',
        'email_unsubscribed_at' => null,
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
        'status' => 'draft',
    ]);

    (new SendEmailCampaignJob($campaign->id))->handle(app(SegmentFilterEngine::class));

    expect(CampaignRecipient::query()->where('campaign_id', $campaign->id)->count())->toBe(1);
    Queue::assertPushed(SendCampaignEmailJob::class, 1);
});

test('first or create on campaign recipients is idempotent for same campaign email pair', function () {
    $campaign = Campaign::factory()->create();

    $first = CampaignRecipient::query()->firstOrCreate(
        ['campaign_id' => $campaign->id, 'email' => 'same@example.test'],
        ['status' => 'pending']
    );

    $second = CampaignRecipient::query()->firstOrCreate(
        ['campaign_id' => $campaign->id, 'email' => 'same@example.test'],
        ['status' => 'pending']
    );

    expect($first->id)->toBe($second->id);
    expect(CampaignRecipient::query()->where('campaign_id', $campaign->id)->where('email', 'same@example.test')->count())->toBe(1);
});

test('unique campaign recipient email constraint rejects direct duplicate insert', function () {
    $campaign = Campaign::factory()->create();

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'email' => 'lock@example.test',
    ]);

    expect(function () use ($campaign): void {
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'email' => 'lock@example.test',
        ]);
    })->toThrow(QueryException::class);
});
