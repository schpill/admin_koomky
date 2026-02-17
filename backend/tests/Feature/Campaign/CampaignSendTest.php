<?php

use App\Jobs\SendEmailCampaignJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('send endpoint dispatches campaign orchestrator and excludes unsubscribed recipients during orchestration', function () {
    Queue::fake();

    $user = User::factory()->create();

    $clientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $clientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);

    Contact::factory()->create(['client_id' => $clientA->id, 'email' => 'ok@campaign.test', 'email_unsubscribed_at' => null]);
    Contact::factory()->create(['client_id' => $clientB->id, 'email' => 'out@campaign.test', 'email_unsubscribed_at' => now()]);

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

    $campaign = \App\Models\Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => $segment->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Hello',
        'content' => 'Hi',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/send');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'sending');

    Queue::assertPushed(SendEmailCampaignJob::class, fn (SendEmailCampaignJob $job) => $job->campaignId === $campaign->id);
});
