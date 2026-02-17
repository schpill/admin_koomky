<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('delivery callback updates recipient status', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'sms']);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'sent']);

    $response = $this->postJson('/webhooks/sms', [
        'event' => 'delivered',
        'recipient_id' => $recipient->id,
    ]);

    $response->assertStatus(200);
    expect($recipient->refresh()->status)->toBe('delivered');
});

test('failure callback stores reason and stop keyword opts out contact', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'sms']);
    $contact = Contact::factory()->create(['sms_opted_out_at' => null]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
    ]);

    $failed = $this->postJson('/webhooks/sms', [
        'event' => 'failed',
        'recipient_id' => $recipient->id,
        'failure_reason' => 'undeliverable',
    ]);

    $failed->assertStatus(200);
    expect($recipient->refresh()->status)->toBe('failed');

    $stop = $this->postJson('/webhooks/sms', [
        'event' => 'opt_out',
        'recipient_id' => $recipient->id,
        'keyword' => 'STOP',
    ]);

    $stop->assertStatus(200);
    expect($contact->refresh()->sms_opted_out_at)->not->toBeNull();
});
