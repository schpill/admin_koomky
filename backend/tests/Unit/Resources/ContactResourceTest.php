<?php

declare(strict_types=1);

use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;

it('transforms contact into json api structure', function () {
    $contact = Contact::factory()->create();

    $resource = (new ContactResource($contact))->toArray(new Request);

    expect($resource)->toHaveKeys(['type', 'id', 'attributes']);
    expect($resource['type'])->toBe('contact');
    expect($resource['id'])->toBe($contact->id);
});

it('includes all contact attributes', function () {
    $contact = Contact::factory()->create();

    $resource = (new ContactResource($contact))->toArray(new Request);
    $attributes = $resource['attributes'];

    expect($attributes)->toHaveKeys([
        'name', 'email', 'phone', 'position', 'is_primary',
        'created_at', 'updated_at',
    ]);
});

it('formats is_primary as boolean', function () {
    $contact = Contact::factory()->primary()->create();

    $resource = (new ContactResource($contact))->toArray(new Request);

    expect($resource['attributes']['is_primary'])->toBeTrue();
});
