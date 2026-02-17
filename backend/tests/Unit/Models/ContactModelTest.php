<?php

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('email subscribed scope excludes unsubscribed contacts', function () {
    Contact::factory()->create(['email_unsubscribed_at' => null]);
    Contact::factory()->create(['email_unsubscribed_at' => now()]);

    $results = Contact::query()->emailSubscribed()->get();

    expect($results)->toHaveCount(1);
});

test('sms opted in scope excludes opted out contacts', function () {
    Contact::factory()->create(['sms_opted_out_at' => null]);
    Contact::factory()->create(['sms_opted_out_at' => now()]);

    $results = Contact::query()->smsOptedIn()->get();

    expect($results)->toHaveCount(1);
});
