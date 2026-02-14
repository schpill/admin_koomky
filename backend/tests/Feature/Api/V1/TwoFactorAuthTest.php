<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can generate 2FA secret', function () {
    $user = User::factory()->create(['two_factor_secret' => null]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/settings/2fa/enable');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => ['qr_code_url', 'secret'],
            'message'
        ]);

    expect($user->refresh()->two_factor_secret)->not->toBeNull();
});
