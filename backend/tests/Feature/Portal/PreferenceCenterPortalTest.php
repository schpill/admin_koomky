<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('signed preference center get returns the three categories', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);

    $url = URL::temporarySignedRoute('portal.preferences.show', now()->addDays(30), [
        'contact' => $contact->id,
    ]);

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.contact_id', $contact->id)
        ->assertJsonCount(3, 'data.preferences');
});

test('signed preference center post updates stored preferences and logs activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    $url = URL::temporarySignedRoute('portal.preferences.update', now()->addDays(30), [
        'contact' => $contact->id,
    ]);

    $this->postJson($url, [
        'preferences' => [
            ['category' => 'newsletter', 'subscribed' => true],
            ['category' => 'promotional', 'subscribed' => false],
            ['category' => 'transactional', 'subscribed' => true],
        ],
    ])->assertOk()
        ->assertJsonPath('data.preferences.1.category', 'promotional')
        ->assertJsonPath('data.preferences.1.subscribed', false);

    $this->assertDatabaseHas('communication_preferences', [
        'contact_id' => $contact->id,
        'category' => 'promotional',
        'subscribed' => false,
    ]);

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => $client::class,
        'description' => 'Communication preferences updated',
    ]);
});

test('invalid preference center signature returns forbidden', function () {
    $contact = Contact::factory()->create();

    $this->getJson('/portal/preferences/'.$contact->id)
        ->assertForbidden();
});

test('personalization renders preferences url for a contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);

    $rendered = app(PersonalizationService::class)->render('{{preferences_url}}', $contact);

    expect($rendered)->toContain('/portal/preferences/'.$contact->id)
        ->and($rendered)->toContain('signature=');
});
