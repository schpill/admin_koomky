<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns current user settings', function () {
    actingAs($this->user)
        ->getJson('/api/v1/settings')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
            ],
        ]);
});

it('updates user profile', function () {
    actingAs($this->user)
        ->putJson('/api/v1/settings', [
            'name' => 'Updated Name',
            'business_name' => 'New Business',
        ])
        ->assertStatus(200);

    expect($this->user->fresh()->name)->toBe('Updated Name');
    expect($this->user->fresh()->business_name)->toBe('New Business');
});

it('updates business information', function () {
    actingAs($this->user)
        ->putJson('/api/v1/settings', [
            'business_name' => 'SARL Koomky',
            'business_address' => '123 rue de Paris',
            'siret' => '12345678901234',
            'vat_number' => 'FR12345678901',
        ])
        ->assertStatus(200);

    $user = $this->user->fresh();
    expect($user->business_name)->toBe('SARL Koomky');
    expect($user->siret)->toBe('12345678901234');
});

it('requires authentication for settings', function () {
    $this->getJson('/api/v1/settings')
        ->assertStatus(401);
});

it('requires authentication for updates', function () {
    $this->putJson('/api/v1/settings', ['name' => 'Test'])
        ->assertStatus(401);
});
