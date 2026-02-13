<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Enable 2FA for the authenticated user.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->twoFactorService->enable($user);

        return response()->json([
            'data' => [
                'type' => 'two_factor_auth',
                'attributes' => [
                    'secret' => $result['secret'],
                    'qr_code_url' => $result['qr_code_url'],
                    'recovery_codes' => $result['recovery_codes'],
                ],
            ],
            'meta' => [
                'message' => 'Two-factor authentication enabled. Please verify your code.',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Verify and confirm 2FA setup.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        try {
            $this->twoFactorService->verify($user, $request->code);

            return response()->json([
                'data' => [
                    'type' => 'two_factor_auth',
                    'attributes' => [
                        'enabled' => true,
                    ],
                ],
                'meta' => [
                    'message' => 'Two-factor authentication verified and enabled.',
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_2fa_code',
                    'message' => 'The provided code is invalid.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Disable 2FA for the authenticated user.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();
        $this->twoFactorService->disable($user);

        return response()->json([
            'data' => [
                'type' => 'two_factor_auth',
                'attributes' => [
                    'enabled' => false,
                ],
            ],
            'meta' => [
                'message' => 'Two-factor authentication disabled.',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Generate new recovery codes.
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();
        $codes = $this->twoFactorService->generateRecoveryCodes($user);

        return response()->json([
            'data' => [
                'type' => 'recovery_codes',
                'attributes' => [
                    'codes' => $codes,
                ],
            ],
            'meta' => [
                'message' => 'New recovery codes generated. Save them securely.',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Confirm 2FA code during login.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        try {
            $result = $this->twoFactorService->confirm(
                $request->session()->get('auth.user_id'),
                $request->code
            );

            // Clear session
            $request->session()->forget(['auth.user_id', 'auth.remember']);

            return response()->json([
                'data' => [
                    'type' => 'session',
                    'attributes' => [
                        'token' => $result['token'],
                        'refresh_token' => $result['refresh_token'],
                        'expires_in' => $result['expires_in'],
                    ],
                ],
                'meta' => [
                    'message' => 'Two-factor authentication confirmed.',
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_2fa_code',
                    'message' => 'The provided code is invalid.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
