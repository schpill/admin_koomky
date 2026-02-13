<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;

it('belongs to a user', function () {
    $client = Client::factory()->create();

    expect($client->user)->toBeInstanceOf(User::class);
});

it('has many contacts', function () {
    $client = Client::factory()->create();
    Contact::factory()->count(3)->create(['client_id' => $client->id]);

    expect($client->contacts)->toHaveCount(3);
    expect($client->contacts->first())->toBeInstanceOf(Contact::class);
});

it('has many activities', function () {
    $client = Client::factory()->create();
    Activity::factory()->count(2)->create(['client_id' => $client->id, 'user_id' => $client->user_id]);

    expect($client->activities)->toHaveCount(2);
});

it('belongs to many tags', function () {
    $client = Client::factory()->create();
    $tags = Tag::factory()->count(2)->create(['user_id' => $client->user_id]);
    $client->tags()->sync($tags->pluck('id'));

    expect($client->tags)->toHaveCount(2);
    expect($client->tags->first())->toBeInstanceOf(Tag::class);
});

it('computes full name from first and last name', function () {
    $client = Client::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'company_name' => null,
    ]);

    expect($client->getFullNameAttribute())->toBe('Jean Dupont');
});

it('returns company name as display name when available', function () {
    $client = Client::factory()->create([
        'company_name' => 'Acme Corp',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);

    expect($client->name)->toBe('Acme Corp');
});

it('returns full name as display name when no company', function () {
    $client = Client::factory()->create([
        'company_name' => null,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);

    expect($client->name)->toBe('Jean Dupont');
});

it('returns active status when not archived', function () {
    $client = Client::factory()->create(['archived_at' => null]);

    expect($client->status)->toBe('active');
});

it('returns archived status when archived_at is set', function () {
    $client = Client::factory()->archived()->create();

    expect($client->status)->toBe('archived');
});

it('scopes active clients', function () {
    $user = User::factory()->create();
    Client::factory()->count(3)->create(['user_id' => $user->id, 'archived_at' => null]);
    Client::factory()->count(2)->create(['user_id' => $user->id, 'archived_at' => now()]);

    $activeClients = Client::where('user_id', $user->id)->active()->get();

    expect($activeClients)->toHaveCount(3);
});

it('scopes archived clients', function () {
    $user = User::factory()->create();
    Client::factory()->count(3)->create(['user_id' => $user->id, 'archived_at' => null]);
    Client::factory()->count(2)->create(['user_id' => $user->id, 'archived_at' => now()]);

    $archivedClients = Client::where('user_id', $user->id)->archived()->get();

    expect($archivedClients)->toHaveCount(2);
});

it('computes billing address from address parts', function () {
    $client = Client::factory()->create([
        'address' => '10 rue de la Paix',
        'postal_code' => '75001',
        'city' => 'Paris',
        'country' => 'France',
    ]);

    $billing = $client->billing_address;

    expect($billing)->toContain('10 rue de la Paix');
    expect($billing)->toContain('75001 Paris');
    expect($billing)->toContain('France');
});

it('returns null billing address when all parts empty', function () {
    $client = Client::factory()->create([
        'address' => null,
        'postal_code' => null,
        'city' => null,
        'country' => null,
    ]);

    // Will contain just a trimmed empty string from postal_code + city
    // but address and country are null, so it depends on filter
    $billing = $client->getBillingAddressAttribute();
    // When only postal_code and city are null, trim gives empty string
    // array_filter removes empty strings, so result could be null
    expect($billing)->toBeNull();
});

it('uses UUID as primary key', function () {
    $client = Client::factory()->create();

    expect($client->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('casts archived_at to datetime', function () {
    $client = Client::factory()->archived()->create();

    expect($client->archived_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('returns searchable array for Meilisearch', function () {
    $client = Client::factory()->create();
    $searchable = $client->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'company_name', 'first_name', 'last_name', 'email', 'reference']);
});
