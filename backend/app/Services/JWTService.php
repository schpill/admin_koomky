<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class JWTService
{
    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    private ?string $tempToken = null;

    /**
     * Generate access and refresh tokens for user.
     */
    public function generateTokens(User $user): void
    {
        // Delete existing tokens to prevent token bloat
        $user->tokens()->delete();

        // Create access token (15 minutes)
        $accessToken = $user->createToken('Koomky API Access Token', [
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->accessToken = $accessToken->plainTextToken;

        // Create refresh token (30 days)
        $refreshToken = $user->createToken('Koomky Refresh Token', [
            'expires_at' => now()->addDays(30),
        ]);

        $this->refreshToken = hash('sha256', $refreshToken->plainTextToken);
    }

    /**
     * Get current access token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken ?? '';
    }

    /**
     * Get current refresh token.
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken ?? '';
    }

    /**
     * Generate temporary token for 2FA verification.
     */
    public function generateTempToken(User $user): string
    {
        $this->tempToken = Str::random(60);

        return $this->tempToken;
    }

    /**
     * Refresh tokens (invalidate old refresh, create new pair).
     */
    public function refreshTokens(User $user): void
    {
        $this->generateTokens($user);
    }
}
