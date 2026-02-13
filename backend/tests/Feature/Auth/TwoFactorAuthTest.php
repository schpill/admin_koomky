<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('SecurePass123!'),
    ]);
});

it('enables 2FA for authenticated user', function () {
    actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/enable')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'secret',
                    'qr_code_url',
                    'recovery_codes',
                ],
            ],
        ]);
});

it('returns 8 recovery codes on enable', function () {
    $response = actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/enable')
        ->assertStatus(200);

    $codes = $response->json('data.attributes.recovery_codes');
    expect($codes)->toHaveCount(8);
});

it('returns a QR code URL on enable', function () {
    $response = actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/enable')
        ->assertStatus(200);

    $qrUrl = $response->json('data.attributes.qr_code_url');
    expect($qrUrl)->toContain('otpauth://');
});

it('validates code format on verify', function () {
    actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/verify', [
            'code' => 'not-a-number',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('requires password to disable 2FA', function () {
    actingAs($this->user)
        ->deleteJson('/api/v1/auth/2fa/disable', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('disables 2FA with correct password', function () {
    // First enable 2FA manually
    $this->user->update([
        'two_factor_secret' => encrypt('testsecret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['CODE-1', 'CODE-2'])),
        'two_factor_enabled' => true,
        'two_factor_enabled_at' => now(),
    ]);

    actingAs($this->user)
        ->deleteJson('/api/v1/auth/2fa/disable', [
            'password' => 'SecurePass123!',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.enabled', false);

    $user = $this->user->fresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_enabled)->toBeFalse();
});

it('requires password to regenerate recovery codes', function () {
    actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/recovery-codes', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('regenerates recovery codes with correct password', function () {
    // Enable 2FA first
    $this->user->update([
        'two_factor_secret' => encrypt('testsecret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['OLD-CODE'])),
        'two_factor_enabled' => true,
    ]);

    $response = actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/recovery-codes', [
            'password' => 'SecurePass123!',
        ])
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'codes',
                ],
            ],
        ]);

    $codes = $response->json('data.attributes.codes');
    expect($codes)->toHaveCount(8);
});

it('requires authentication for 2FA enable', function () {
    $this->postJson('/api/v1/auth/2fa/enable')
        ->assertStatus(401);
});

it('requires authentication for 2FA disable', function () {
    $this->deleteJson('/api/v1/auth/2fa/disable')
        ->assertStatus(401);
});

it('verifies 2FA setup with valid code', function () {
    // Enable 2FA to get cached setup data
    $response = actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/enable')
        ->assertStatus(200);

    $secret = $response->json('data.attributes.secret');

    // Generate valid TOTP code
    $google2FA = new \PragmaRX\Google2FA\Google2FA;
    $validCode = $google2FA->getCurrentOtp($secret);

    actingAs($this->user)
        ->postJson('/api/v1/auth/2fa/verify', [
            'code' => $validCode,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.enabled', true);

    expect($this->user->fresh()->two_factor_enabled)->toBeTrue();
});
