<?php

use App\Models\Client;
use App\Models\Contact;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('render preview resolves all expected placeholders', function () {
    $service = app(PersonalizationService::class);

    $rendered = $service->renderPreview(
        'Hi {{first_name}} {{last_name}} from {{company}} '
        .'{{client.industry}} {{client.department}} {{client.address}} {{client.zip_code}} '
        .'{{contact.position}} {{client.reference}} {{unknown}}'
    );

    expect($rendered)->toContain('Marie Dupont');
    expect($rendered)->toContain('Acme Corp');
    expect($rendered)->toContain('Wedding Planner');
    expect($rendered)->toContain('75');
    expect($rendered)->toContain('12 rue de la Paix');
    expect($rendered)->toContain('75001');
    expect($rendered)->toContain('Directrice');
    expect($rendered)->toContain('REF-001');
    expect($rendered)->not->toContain('{{unknown}}');
});

test('render resolves extended client placeholders from real contact context', function () {
    $client = Client::factory()->create([
        'name' => 'Client Pro',
        'industry' => 'Coiffeur',
        'department' => '60',
        'address' => '8 avenue des fleurs',
        'zip_code' => '60000',
    ]);

    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Paul',
        'position' => 'Manager',
    ]);

    $service = app(PersonalizationService::class);

    $rendered = $service->render(
        '{{first_name}} {{client.industry}} {{client.department}} {{client.address}} {{client.zip_code}} {{contact.position}}',
        $contact
    );

    expect($rendered)->toBe('Paul Coiffeur 60 8 avenue des fleurs 60000 Manager');
});
