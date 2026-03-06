<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\EmailTrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('click tracking stores clicked url and only scores the first click for a recipient url pair', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $contact = Contact::factory()->for($client)->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
    ]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);
    $destination = 'https://example.com/offer';

    $this->get('/t/click/'.$token.'?url='.urlencode($destination))
        ->assertRedirect($destination);

    $this->get('/t/click/'.$token.'?url='.urlencode($destination))
        ->assertRedirect($destination);

    $recipient->refresh();

    expect($recipient->clicked_at)->not->toBeNull();

    $clicks = DB::table('campaign_link_clicks')
        ->where('recipient_id', $recipient->id)
        ->where('url', $destination)
        ->count();

    expect($clicks)->toBe(2);

    $scoreEvents = DB::table('contact_score_events')
        ->where('contact_id', $contact->id)
        ->where('event', 'email_clicked')
        ->count();

    expect($scoreEvents)->toBe(1);
});
