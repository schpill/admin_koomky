<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use App\Enums\AuthEventType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Auth\AuthenticationException;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class AuthService
{
    private const LOCKOUT_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 15; // minutes
    private const REFRESH_TOKEN_TTL = 43200; // 30 days in seconds

    public function __construct(
        private JWTService $jwtService
    ) {}

    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->jwtService->generateTokens($user);

        // Log registration
        AuditLog::create([
            'user_id' => $user->id,
            'event' => AuthEventType::LOGIN, // Treat registration as first login
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'data' => [
                'type' => 'user',
                'id' => $user->id,
                'attributes' => $user->toArray(),
            ],
            'meta' => [
                'token' => [
                    'access_token' => $this->jwtService->getAccessToken(),
                    'refresh_token' => $this->jwtService->getRefreshToken(),
                    'expires_in' => config('sanctum.expiration') ?? 15 * 60, // 15 minutes in seconds
                ],
            ],
            'status_code' => 201,
        ];
    }

    /**
     * Login user and return tokens.
     */
    public function login(array $credentials, string $ip): array
    {
        // Check for lockout
        if ($this->isLocked($credentials['email'])) {
            return [
                'error' => [
                    'message' => 'Account is temporarily locked due to too many failed attempts.',
                    'retry_after' => $this->getLockoutTimeRemaining($credentials['email']),
                ],
                'status_code' => 429,
            ];
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->incrementFailedAttempts($credentials['email']);
            return [
                'error' => [
                    'message' => 'The provided credentials are incorrect.',
                ],
                'status_code' => 401,
            ];
        }

        // Check if 2FA is enabled
        if ($user->hasTwoFactorEnabled()) {
            return [
                'data' => [
                    'type' => 'auth_challenge',
                    'attributes' => [
                        'requires_two_factor' => true,
                    ],
                ],
                'meta' => [
                    'temp_token' => $this->jwtService->generateTempToken($user),
                ],
                'status_code' => 202,
            ];
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($credentials['email']);

        // Generate tokens
        $this->jwtService->generateTokens($user);

        // Log successful login
        AuditLog::create([
            'user_id' => $user->id,
            'event' => AuthEventType::LOGIN,
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'data' => [
                'type' => 'user',
                'id' => $user->id,
                'attributes' => $user->toArray(),
            ],
            'meta' => [
                'token' => [
                    'access_token' => $this->jwtService->getAccessToken(),
                    'refresh_token' => $this->jwtService->getRefreshToken(),
                    'expires_in' => config('sanctum.expiration') ?? 15 * 60,
                ],
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Logout user and invalidate tokens.
     */
    public function logout(User $user): void
    {
        // Delete all tokens for the user
        $user->tokens()->delete();

        // Log logout
        AuditLog::create([
            'user_id' => $user->id,
            'event' => AuthEventType::LOGOUT,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Refresh access token.
     */
    public function refresh(User $user): array
    {
        // Revoke current refresh token and issue new one
        $this->jwtService->refreshTokens($user);

        return [
            'data' => [
                'type' => 'token',
                'attributes' => [
                    'access_token' => $this->jwtService->getAccessToken(),
                    'refresh_token' => $this->jwtService->getRefreshToken(),
                    'expires_in' => config('sanctum.expiration') ?? 15 * 60,
                ],
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email): void
    {
        // Always return success to prevent email enumeration
        // In production, queue an email with reset token
    }

    /**
     * Reset user password.
     */
    public function resetPassword(array $data): array
    {
        // TODO: Implement password reset with token verification
        return [
            'data' => [
                'message' => 'Password has been reset successfully.',
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Check if user is locked.
     */
    private function isLocked(string $email): bool
    {
        $key = "lockout:{$email}";
        $attempts = Redis::get($key, 0);

        return $attempts >= self::LOCKOUT_ATTEMPTS;
    }

    /**
     * Get remaining lockout time in seconds.
     */
    private function getLockoutTimeRemaining(string $email): int
    {
        $key = "lockout:{$email}:until";
        $lockoutUntil = Redis::get($key);

        if (!$lockoutUntil) {
            return 0;
        }

        return max(0, Carbon::parse($lockoutUntil)->diffInSeconds(now()));
    }

    /**
     * Increment failed login attempts.
     */
    private function incrementFailedAttempts(string $email): void
    {
        $key = "lockout:{$email}";
        $attempts = Redis::incr($key);

        // Set lockout if max attempts reached
        if ($attempts >= self::LOCKOUT_ATTEMPTS) {
            Redis::setex("lockout:{$email}:until", self::LOCKOUT_DURATION * 60, now()->addMinutes(self::LOCKOUT_DURATION)->toIso8601String());
        }

        // Expire attempts counter after 15 minutes
        Redis::expire($key, self::LOCKOUT_DURATION * 60);
    }

    /**
     * Clear failed login attempts.
     */
    private function clearFailedAttempts(string $email): void
    {
        Redis::del("lockout:{$email}");
        Redis::del("lockout:{$email}:until");
    }
}
