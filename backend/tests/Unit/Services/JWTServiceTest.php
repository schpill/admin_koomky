<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\JWTService;

beforeEach(function () {
    $this->jwtService = new JWTService();
});

it('generates access and refresh tokens for a user', function () {
    $user = User::factory()->create();

    $this->jwtService->generateTokens($user);

    expect($this->jwtService->getAccessToken())->not->toBeEmpty();
    expect($this->jwtService->getRefreshToken())->not->toBeEmpty();
});

it('returns empty strings before token generation', function () {
    expect($this->jwtService->getAccessToken())->toBe('');
    expect($this->jwtService->getRefreshToken())->toBe('');
});

it('deletes existing tokens before generating new ones', function () {
    $user = User::factory()->create();

    // Generate first set
    $this->jwtService->generateTokens($user);
    $firstToken = $this->jwtService->getAccessToken();

    // Generate second set
    $this->jwtService->generateTokens($user);
    $secondToken = $this->jwtService->getAccessToken();

    expect($firstToken)->not->toBe($secondToken);
    // Old tokens should be deleted, only new ones remain
    expect($user->tokens()->count())->toBe(2); // access + refresh
});

it('generates a temporary token for 2FA', function () {
    $user = User::factory()->create();

    $tempToken = $this->jwtService->generateTempToken($user);

    expect($tempToken)->toBeString();
    expect(strlen($tempToken))->toBe(60);
});

it('refreshes tokens by generating a new pair', function () {
    $user = User::factory()->create();

    $this->jwtService->generateTokens($user);
    $oldAccess = $this->jwtService->getAccessToken();
    $oldRefresh = $this->jwtService->getRefreshToken();

    $this->jwtService->refreshTokens($user);

    expect($this->jwtService->getAccessToken())->not->toBe($oldAccess);
    expect($this->jwtService->getRefreshToken())->not->toBe($oldRefresh);
});
