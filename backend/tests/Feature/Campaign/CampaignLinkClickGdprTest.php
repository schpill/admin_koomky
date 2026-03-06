<?php

use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full export includes campaign link clicks in gdpr archive', function () {
    $user = User::factory()->create();
    $client = Client::query()->create([
        'user_id' => $user->id,
        'reference' => 'CLI-2026-1004',
        'name' => 'GDPR Client',
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
    $contact = Contact::factory()->for($client)->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
    ]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
    ]);

    CampaignLinkClick::factory()->create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'recipient_id' => $recipient->id,
        'contact_id' => $contact->id,
        'url' => 'https://example.com/privacy',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/export/full');

    $response->assertOk();

    $content = $response->streamedContent();
    $archivePath = tempnam(sys_get_temp_dir(), 'koomky-export-');
    file_put_contents($archivePath, $content);

    $zip = new ZipArchive;
    expect($zip->open($archivePath))->toBeTrue();

    $json = $zip->getFromName('export.json');
    $zip->close();

    $payload = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

    expect($payload['campaign_link_clicks'][0]['url'] ?? null)->toBe('https://example.com/privacy');
});
