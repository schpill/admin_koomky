<?php

use App\Mail\CampaignTestMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('test send endpoint sends email to specified address without tracking mutation', function () {
    Mail::fake();

    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Test Subject',
        'content' => '<p>Test content</p>',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'email' => 'qa@campaign.test',
        ]);

    $response->assertStatus(200);

    Mail::assertSent(CampaignTestMail::class, function (CampaignTestMail $mail): bool {
        return $mail->hasTo('qa@campaign.test');
    });
});
