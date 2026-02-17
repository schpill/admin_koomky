<?php

use App\Models\Campaign;
use App\Models\CampaignAttachment;
use App\Models\CampaignRecipient;
use App\Models\CampaignTemplate;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('campaign factory creates valid model', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign->id)->toBeString();
    expect($campaign->name)->toBeString();
    expect($campaign->status)->toBeString();
});

test('campaign relationships are configured', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['user_id' => $user->id]);
    $template = CampaignTemplate::factory()->create(['user_id' => $user->id]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => $segment->id,
        'template_id' => $template->id,
    ]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
    ]);

    $attachment = CampaignAttachment::factory()->create([
        'campaign_id' => $campaign->id,
    ]);

    expect($campaign->user->id)->toBe($user->id);
    expect($campaign->segment?->id)->toBe($segment->id);
    expect($campaign->template?->id)->toBe($template->id);
    expect($campaign->recipients->first()?->id)->toBe($recipient->id);
    expect($campaign->attachments->first()?->id)->toBe($attachment->id);
});

test('campaign scopes filter by type and status', function () {
    Campaign::factory()->create(['type' => 'email', 'status' => 'draft']);
    Campaign::factory()->create(['type' => 'sms', 'status' => 'sent']);

    expect(Campaign::query()->byType('email')->count())->toBe(1);
    expect(Campaign::query()->byStatus('sent')->count())->toBe(1);
});

test('campaign status transitions are validated', function () {
    $campaign = Campaign::factory()->create(['status' => 'draft']);

    expect($campaign->canTransitionTo('scheduled'))->toBeTrue();
    expect($campaign->canTransitionTo('sent'))->toBeFalse();
});
