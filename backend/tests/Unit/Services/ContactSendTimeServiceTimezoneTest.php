<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactSendTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('get optimal hour uses contact local timezone for opened history', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'timezone' => 'America/New_York',
    ]);
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    foreach ([
        '2026-03-06 14:00:00',
        '2026-03-07 14:15:00',
        '2026-03-08 14:30:00',
    ] as $openedAtUtc) {
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'opened_at' => Carbon::parse($openedAtUtc, 'UTC'),
        ]);
    }

    $hour = app(ContactSendTimeService::class)->getOptimalHour($contact, $user);

    expect($hour)->toBe(9);
});

test('get next send delay computes next local occurrence and converts back to utc', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-06 13:00:00', 'UTC'));

    $delay = app(ContactSendTimeService::class)->getNextSendDelay(
        9,
        24,
        'America/New_York'
    );

    expect($delay)->toBe(3600);

    Carbon::setTestNow();
});
