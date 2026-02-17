<?php

use App\Models\Client;
use App\Models\Contact;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('personalization replaces built in variables', function () {
    $client = Client::factory()->create(['name' => 'Acme Inc']);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.test',
    ]);

    $service = app(PersonalizationService::class);

    $content = $service->render(
        'Hello {{first_name}} {{last_name}} from {{company}} - {{email}}',
        $contact
    );

    expect($content)->toBe('Hello Jane Doe from Acme Inc - jane@example.test');
});

test('missing variables are handled gracefully', function () {
    $contact = Contact::factory()->create(['first_name' => 'John']);

    $service = app(PersonalizationService::class);

    $content = $service->render('Hi {{first_name}} {{unknown_var}}', $contact);

    expect($content)->toBe('Hi John ');
});

test('personalization escapes html values', function () {
    $contact = Contact::factory()->create([
        'first_name' => '<script>alert(1)</script>',
    ]);

    $service = app(PersonalizationService::class);

    $content = $service->render('Hi {{first_name}}', $contact);

    expect($content)->toContain('&lt;script&gt;');
    expect($content)->not->toContain('<script>');
});
