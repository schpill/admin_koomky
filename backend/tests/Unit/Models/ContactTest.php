<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contact;

it('belongs to a client', function () {
    $contact = Contact::factory()->create();

    expect($contact->client)->toBeInstanceOf(Client::class);
});

it('computes full name from first and last name', function () {
    $contact = Contact::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Martin',
    ]);

    expect($contact->name)->toBe('Marie Martin');
});

it('casts is_primary to boolean', function () {
    $contact = Contact::factory()->primary()->create();

    expect($contact->is_primary)->toBeTrue();
    expect($contact->is_primary)->toBeBool();
});

it('scopes primary contacts', function () {
    $client = Client::factory()->create();
    Contact::factory()->primary()->create(['client_id' => $client->id]);
    Contact::factory()->count(2)->create(['client_id' => $client->id, 'is_primary' => false]);

    $primaryContacts = Contact::where('client_id', $client->id)->primary()->get();

    expect($primaryContacts)->toHaveCount(1);
});

it('uses UUID as primary key', function () {
    $contact = Contact::factory()->create();

    expect($contact->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('has correct fillable attributes', function () {
    $contact = new Contact;

    expect($contact->getFillable())->toContain('first_name');
    expect($contact->getFillable())->toContain('last_name');
    expect($contact->getFillable())->toContain('email');
    expect($contact->getFillable())->toContain('phone');
    expect($contact->getFillable())->toContain('is_primary');
    expect($contact->getFillable())->toContain('position');
});
