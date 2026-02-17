<?php

use App\Jobs\SendCampaignSmsJob;
use App\Jobs\SendSmsCampaignJob;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sms send resolves segment validates phones excludes opted out and dispatches jobs', function () {
    Queue::fake();

    $user = User::factory()->create([
        'sms_settings' => [
            'provider' => 'twilio',
            'account_sid' => 'AC123',
            'auth_token' => 'token',
            'from' => '+33123456789',
        ],
    ]);

    $clientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $clientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $clientC = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);

    Contact::factory()->create(['client_id' => $clientA->id, 'phone' => '+33612345678', 'sms_opted_out_at' => null]);
    Contact::factory()->create(['client_id' => $clientB->id, 'phone' => '0612345678', 'sms_opted_out_at' => null]);
    Contact::factory()->create(['client_id' => $clientC->id, 'phone' => '+33687654321', 'sms_opted_out_at' => now()]);

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
        'type' => 'sms',
        'status' => 'draft',
        'content' => 'Hi {{first_name}}',
        'settings' => ['throttle_rate_per_minute' => 30],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/send');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'sending');

    Queue::assertPushed(SendSmsCampaignJob::class, fn (SendSmsCampaignJob $job) => $job->campaignId === $campaign->id);

    app(SendSmsCampaignJob::class, ['campaignId' => $campaign->id])->handle(
        app(\App\Services\SegmentFilterEngine::class),
        app(\App\Services\PhoneValidationService::class)
    );

    Queue::assertPushed(SendCampaignSmsJob::class);
});
