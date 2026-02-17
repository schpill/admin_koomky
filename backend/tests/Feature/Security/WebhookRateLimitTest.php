<?php

use App\Models\CampaignRecipient;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('email webhook route is rate limited', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $recipient = CampaignRecipient::factory()->create();
    $throttled = false;

    for ($i = 0; $i < 70; $i++) {
        $response = $this->postJson('/webhooks/email', [
            'event' => 'delivery',
            'recipient_id' => $recipient->id,
        ]);

        if ($response->status() === 429) {
            $throttled = true;
            break;
        }
    }

    expect($throttled)->toBeTrue();
});
