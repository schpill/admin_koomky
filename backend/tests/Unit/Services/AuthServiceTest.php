<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthService;
use App\Services\JWTService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushall();
    $this->jwtService = new JWTService();
    $this->authService = new AuthService($this->jwtService);
});

it('registers a new user', function () {
    $result = $this->authService->register([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'SecurePassword123!',
    ]);

    expect($result['status_code'])->toBe(201);
    expect($result['data']['type'])->toBe('user');
    expect($result['meta']['token'])->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
});

it('creates audit log on registration', function () {
    $this->authService->register([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'SecurePassword123!',
    ]);

    expect(AuditLog::count())->toBe(1);
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('SecurePassword123!'),
    ]);

    $result = $this->authService->login([
        'email' => 'user@example.com',
        'password' => 'SecurePassword123!',
    ], '127.0.0.1');

    expect($result['status_code'])->toBe(200);
    expect($result['data']['type'])->toBe('user');
    expect($result['meta']['token']['access_token'])->not->toBeEmpty();
});

it('returns 401 for invalid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('SecurePassword123!'),
    ]);

    $result = $this->authService->login([
        'email' => 'user@example.com',
        'password' => 'WrongPassword',
    ], '127.0.0.1');

    expect($result['status_code'])->toBe(401);
    expect($result['error']['message'])->toBe('The provided credentials are incorrect.');
});

it('returns 401 for non-existent user', function () {
    $result = $this->authService->login([
        'email' => 'nonexistent@example.com',
        'password' => 'SomePassword123!',
    ], '127.0.0.1');

    expect($result['status_code'])->toBe(401);
});

it('locks account after 5 failed attempts', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('SecurePassword123!'),
    ]);

    // Make 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->authService->login([
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ], '127.0.0.1');
    }

    // 6th attempt should be locked
    $result = $this->authService->login([
        'email' => 'user@example.com',
        'password' => 'SecurePassword123!',
    ], '127.0.0.1');

    expect($result['status_code'])->toBe(429);
    expect($result['error']['message'])->toContain('temporarily locked');
    expect($result['error']['retry_after'])->toBeGreaterThan(0);
});

it('returns 202 when user has 2FA enabled', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('SecurePassword123!'),
    ]);
    $user->forceFill([
        'two_factor_secret' => encrypt('testsecret'),
        'two_factor_enabled' => true,
    ])->save();

    $result = $this->authService->login([
        'email' => 'user@example.com',
        'password' => 'SecurePassword123!',
    ], '127.0.0.1');

    expect($result['status_code'])->toBe(202);
    expect($result['data']['type'])->toBe('auth_challenge');
    expect($result['data']['attributes']['requires_two_factor'])->toBeTrue();
    expect($result['meta']['temp_token'])->not->toBeEmpty();
});

it('logs out user and deletes tokens', function () {
    $user = User::factory()->create();
    $user->createToken('test-token');

    expect($user->tokens()->count())->toBeGreaterThan(0);

    $this->authService->logout($user);

    expect($user->tokens()->count())->toBe(0);
});

it('creates audit log on logout', function () {
    $user = User::factory()->create();

    $this->authService->logout($user);

    expect(AuditLog::where('user_id', $user->id)->count())->toBe(1);
});

it('refreshes tokens', function () {
    $user = User::factory()->create();

    $result = $this->authService->refresh($user);

    expect($result['status_code'])->toBe(200);
    expect($result['data']['type'])->toBe('token');
    expect($result['data']['attributes'])->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
});

it('generates tokens for user', function () {
    $user = User::factory()->create();

    $result = $this->authService->generateTokens($user);

    expect($result)->toHaveKeys(['token', 'refresh_token', 'expires_in']);
    expect($result['token'])->not->toBeEmpty();
});

it('resets password stub returns success', function () {
    $result = $this->authService->resetPassword([
        'token' => 'some-token',
        'email' => 'user@example.com',
        'password' => 'NewPassword123!',
    ]);

    expect($result['status_code'])->toBe(200);
});

it('sendPasswordResetLink returns void without error', function () {
    // Should not throw, even for nonexistent emails (prevents email enumeration)
    $result = $this->authService->sendPasswordResetLink('nonexistent@example.com');

    expect($result)->toBeNull();
});

it('clears failed attempts on successful login', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('SecurePassword123!'),
    ]);

    // Add some failed attempts
    Redis::set('lockout:user@example.com', 3);

    $this->authService->login([
        'email' => 'user@example.com',
        'password' => 'SecurePassword123!',
    ], '127.0.0.1');

    $attempts = Redis::get('lockout:user@example.com');
    expect($attempts)->toBeNull();
});
