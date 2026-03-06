<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('get preferences creates default subscribed categories', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);

    $preferences = app(\App\Services\PreferenceCenterService::class)->getPreferences($contact);

    expect($preferences)->toHaveCount(3)
        ->and($preferences->pluck('category')->all())->toBe([
            'newsletter',
            'promotional',
            'transactional',
        ])
        ->and($preferences->every(fn ($preference) => $preference->subscribed === true))->toBeTrue();
});

test('is allowed respects stored communication preferences and keeps transactional emails allowed', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);

    $service = app(\App\Services\PreferenceCenterService::class);
    $service->updatePreference($contact, 'promotional', false);

    expect($service->isAllowed($contact, 'promotional'))->toBeFalse()
        ->and($service->isAllowed($contact, 'newsletter'))->toBeTrue()
        ->and($service->isAllowed($contact, 'transactional'))->toBeTrue();
});
