<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full gdpr export includes communication preferences', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);

    app(\App\Services\PreferenceCenterService::class)->updatePreference($contact, 'promotional', false);

    $payload = app(DataExportService::class)->exportUserData($user);

    expect($payload['communication_preferences'])->toHaveCount(3)
        ->and($payload['communication_preferences'][1]['category'])->toBe('promotional')
        ->and($payload['communication_preferences'][1]['subscribed'])->toBeFalse();
});
