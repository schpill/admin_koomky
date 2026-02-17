<?php

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('test sms can be sent to a specified phone number', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'sms-test-123'], 201),
    ]);

    $user = User::factory()->create([
        'sms_settings' => [
            'provider' => 'twilio',
            'account_sid' => 'AC123',
            'auth_token' => 'token',
            'from' => '+33123456789',
        ],
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'sms',
        'content' => 'SMS content',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'phone' => '+33612345678',
        ]);

    $response->assertStatus(200);
});
