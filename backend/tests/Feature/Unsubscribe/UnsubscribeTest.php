<?php

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('valid signed token unsubscribes contact email', function () {
    $contact = Contact::factory()->create([
        'email' => 'test@unsubscribe.test',
        'email_unsubscribed_at' => null,
    ]);

    $url = URL::temporarySignedRoute('unsubscribe', now()->addMinutes(30), [
        'contact' => $contact->id,
    ]);

    $response = $this->getJson($url);

    $response->assertStatus(200);

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
    ]);

    expect($contact->refresh()->email_unsubscribed_at)->not->toBeNull();
});

test('invalid token returns forbidden', function () {
    $contact = Contact::factory()->create();

    $response = $this->getJson('/unsubscribe/'.$contact->id);

    $response->assertStatus(403);
});

test('already unsubscribed contact remains idempotent', function () {
    $contact = Contact::factory()->create([
        'email_unsubscribed_at' => now()->subDay(),
    ]);

    $url = URL::temporarySignedRoute('unsubscribe', now()->addMinutes(30), [
        'contact' => $contact->id,
    ]);

    $response = $this->getJson($url);

    $response->assertStatus(200);
    expect($contact->refresh()->email_unsubscribed_at)->not->toBeNull();
});
