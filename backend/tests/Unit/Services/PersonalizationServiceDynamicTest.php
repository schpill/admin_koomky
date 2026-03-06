<?php

use App\Models\Client;
use App\Models\Contact;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('personalization renders dynamic content using contact client and email score', function () {
    $client = Client::factory()->create([
        'industry' => 'Wedding Planner',
        'department' => '75',
    ]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Jane',
        'email_score' => 80,
    ]);

    $content = app(PersonalizationService::class)->render(
        '{{#if client.industry == "Wedding Planner"}}VIP {{contact.first_name}}{{else}}Standard{{/if}} {{#if email_score >= 50}}Paris{{else}}Province{{/if}}',
        $contact
    );

    expect($content)->toBe('VIP Jane Paris');
});

test('preview renders the high score branch for dynamic content', function () {
    $content = app(PersonalizationService::class)->renderPreview(
        '{{#if email_score >= 50}}High{{else}}Low{{/if}}'
    );

    expect($content)->toBe('High');
});
