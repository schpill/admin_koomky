<?php

use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('full report returns summary links and daily timeline', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'sent',
    ]);

    $recipientA = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'email' => 'a@example.test',
        'status' => 'clicked',
        'delivered_at' => Carbon::parse('2026-03-04 08:00:00'),
        'opened_at' => Carbon::parse('2026-03-04 10:00:00'),
        'clicked_at' => Carbon::parse('2026-03-04 11:00:00'),
    ]);

    $recipientB = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'email' => 'b@example.test',
        'status' => 'opened',
        'delivered_at' => Carbon::parse('2026-03-05 08:00:00'),
        'opened_at' => Carbon::parse('2026-03-05 09:00:00'),
    ]);

    CampaignLinkClick::factory()->create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'recipient_id' => $recipientA->id,
        'contact_id' => $recipientA->contact_id,
        'url' => 'https://example.test/offer',
        'clicked_at' => Carbon::parse('2026-03-04 11:00:00'),
    ]);
    CampaignLinkClick::factory()->create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'recipient_id' => $recipientB->id,
        'contact_id' => $recipientB->contact_id,
        'url' => 'https://example.test/offer',
        'clicked_at' => Carbon::parse('2026-03-05 10:00:00'),
    ]);

    $report = app(\App\Services\CampaignReportService::class)->getFullReport($campaign);

    expect($report['summary']['sent'])->toBe(2)
        ->and($report['summary']['opened'])->toBe(2)
        ->and($report['summary']['clicked'])->toBe(1)
        ->and($report['links'])->toHaveCount(1)
        ->and($report['links'][0]['unique_clicks'])->toBe(2)
        ->and($report['timeline'])->toHaveCount(2);
});
