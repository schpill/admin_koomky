<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'business_name' => $request->business_name,
        ]);

        return $this->issueTokens($user, 'User registered successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey($request), 900);
            return $this->error('Invalid login credentials', 401);
        }

        RateLimiter::clear($this->throttleKey($request));

        $user = User::where('email', $request->email)->firstOrFail();
        
        if ($user->two_factor_confirmed_at) {
            // Issue a temporary token with only 2fa-pending ability
            $accessToken = $user->createToken('2fa_token', ['2fa-pending'], now()->addMinutes(15))->plainTextToken;
            
            return $this->success([
                'requires_2fa' => true,
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 60,
            ], 'Two-factor authentication required');
        }

        return $this->issueTokens($user, 'Login successful');
    }

    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        
        /** @var User $user */
        $user = $request->user();
        
        if (!$user->two_factor_secret) {
            return $this->error('2FA not enabled', 400);
        }

        $valid = Google2FA::verifyKey((string)$user->two_factor_secret, (string)$request->input('code'));

        if (!$valid) {
            return $this->error('Invalid verification code', 422);
        }

        // Revoke the temporary token
        $user->currentAccessToken()->delete();

        return $this->issueTokens($user, '2FA verified successfully');
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (!$token instanceof \Laravel\Sanctum\PersonalAccessToken || !$token->can('refresh')) {
            return $this->error('Invalid refresh token', 401);
        }

        // Delete the current refresh token (rotation)
        $token->delete();

        return $this->issueTokens($user, 'Token refreshed successfully');
    }

    protected function issueTokens(User $user, string $message, int $code = 200): JsonResponse
    {
        // For a real app, we might want different abilities for access and refresh tokens
        $accessToken = $user->createToken('access_token', ['access'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7))->plainTextToken;

        return $this->success([
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 15 * 60, // 15 minutes
        ], $message, $code);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user(), 'User profile retrieved');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->success(null, __($status))
            : $this->error(__($status), 422);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->success(null, __($status))
            : $this->error(__($status), 422);
    }

    protected function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        abort(429, 'Too many login attempts. Please try again in ' . $seconds . ' seconds.');
    }

    protected function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
