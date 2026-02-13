<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->google2FA = new Google2FA;
    $this->service = new TwoFactorAuthService($this->google2FA);
});

it('enables 2FA and returns setup data', function () {
    $user = User::factory()->create();

    $result = $this->service->enable($user);

    expect($result)->toHaveKeys(['secret', 'qr_code_url', 'recovery_codes']);
    expect($result['secret'])->not->toBeEmpty();
    expect($result['qr_code_url'])->toContain('otpauth://');
    expect($result['recovery_codes'])->toHaveCount(8);
});

it('stores setup data in cache for 15 minutes', function () {
    $user = User::factory()->create();

    $this->service->enable($user);

    $cached = Cache::get("2fa:setup:{$user->id}");
    expect($cached)->not->toBeNull();
    expect($cached)->toHaveKeys(['secret', 'recovery_codes']);
});

it('verifies 2FA with valid TOTP code', function () {
    $user = User::factory()->create();

    // Enable 2FA to get cached setup data
    $setupResult = $this->service->enable($user);
    $secret = $setupResult['secret'];

    // Generate a valid TOTP code
    $validCode = $this->google2FA->getCurrentOtp($secret);

    $result = $this->service->verify($user, $validCode);

    expect($result)->toBeTrue();
    expect($user->fresh()->two_factor_enabled)->toBeTrue();
    expect($user->fresh()->two_factor_enabled_at)->not->toBeNull();
});

it('throws exception when setup has expired', function () {
    $user = User::factory()->create();
    // Don't call enable(), so no cache exists

    $this->service->verify($user, '123456');
})->throws(Exception::class, 'Two-factor authentication setup expired');

it('rejects invalid TOTP code during verify', function () {
    $user = User::factory()->create();
    $this->service->enable($user);

    $result = $this->service->verify($user, '000000');

    expect($result)->toBeFalse();
});

it('disables 2FA', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('testsecret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['CODE-1'])),
        'two_factor_enabled' => true,
        'two_factor_enabled_at' => now(),
    ])->save();

    $this->service->disable($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
    expect($user->two_factor_enabled)->toBeFalse();
    expect($user->two_factor_enabled_at)->toBeNull();
});

it('verifies code during login with valid TOTP', function () {
    $secret = $this->google2FA->generateSecretKey();
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_enabled' => true,
    ])->save();

    $validCode = $this->google2FA->getCurrentOtp($secret);

    expect($this->service->verifyCode($user, $validCode))->toBeTrue();
});

it('verifies code during login with valid recovery code', function () {
    $secret = $this->google2FA->generateSecretKey();
    $user = User::factory()->create();
    $recoveryCodes = ['AAAA-BBBB', 'CCCC-DDDD', 'EEEE-FFFF'];
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        'two_factor_enabled' => true,
    ])->save();

    expect($this->service->verifyCode($user, 'AAAA-BBBB'))->toBeTrue();

    // Recovery code should be consumed
    $remaining = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($remaining)->not->toContain('AAAA-BBBB');
    expect($remaining)->toHaveCount(2);
});

it('rejects invalid code during login', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt($this->google2FA->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['VALID-CODE'])),
        'two_factor_enabled' => true,
    ])->save();

    expect($this->service->verifyCode($user, 'INVALID'))->toBeFalse();
});

it('returns true for verifyCode when 2FA not enabled', function () {
    $user = User::factory()->create();

    expect($this->service->verifyCode($user, 'anything'))->toBeTrue();
});

it('generates new recovery codes', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('testsecret'),
        'two_factor_enabled' => true,
    ])->save();

    $codes = $this->service->generateRecoveryCodes($user);

    expect($codes)->toHaveCount(8);
    // Verify codes are persisted
    $stored = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($stored)->toHaveCount(8);
});

it('confirms 2FA during login and returns tokens', function () {
    $secret = $this->google2FA->generateSecretKey();
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['REC-001'])),
        'two_factor_enabled' => true,
        'two_factor_enabled_at' => now(),
    ])->save();

    $validCode = $this->google2FA->getCurrentOtp($secret);

    $result = $this->service->confirm($user->id, $validCode);

    expect($result)->toHaveKeys(['token', 'refresh_token', 'expires_in']);
    expect($result['token'])->not->toBeEmpty();
});

it('throws exception on confirm with invalid code', function () {
    $secret = $this->google2FA->generateSecretKey();
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['REC-001'])),
        'two_factor_enabled' => true,
        'two_factor_enabled_at' => now(),
    ])->save();

    $this->service->confirm($user->id, '000000');
})->throws(\Exception::class, 'Invalid');

it('clears cache after successful verification', function () {
    $user = User::factory()->create();

    $setupResult = $this->service->enable($user);
    $secret = $setupResult['secret'];
    $validCode = $this->google2FA->getCurrentOtp($secret);

    $this->service->verify($user, $validCode);

    expect(Cache::get("2fa:setup:{$user->id}"))->toBeNull();
});
