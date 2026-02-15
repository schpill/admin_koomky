<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdateBusinessRequest;
use App\Http\Requests\Api\V1\Settings\UpdateProfileRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class UserSettingsController extends Controller
{
    use ApiResponse;

    public function profile(Request $request): JsonResponse
    {
        return $this->success($request->user(), 'User settings retrieved');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update($request->validated());

        return $this->success($user, 'Profile updated successfully');
    }

    public function updateBusiness(UpdateBusinessRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update($request->validated());

        return $this->success($user, 'Business settings updated successfully');
    }

    public function enable2fa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $secret = Google2FA::generateSecretKey();
        $user->update(['two_factor_secret' => $secret]);

        $qrCodeUrl = Google2FA::getQRCodeInline(
            (string)config('app.name'),
            (string)$user->email,
            (string)$secret
        );

        return $this->success([
            'qr_code_url' => $qrCodeUrl,
            'secret' => $secret,
        ], '2FA secret generated. Please confirm.');
    }

    public function confirm2fa(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        
        /** @var User $user */
        $user = $request->user();
        $valid = Google2FA::verifyKey((string)$user->two_factor_secret, (string)$request->input('code'));

        if (!$valid) {
            return $this->error('Invalid verification code', 422);
        }

        $user->update(['two_factor_confirmed_at' => now()]);

        return $this->success(null, 'Two-factor authentication enabled successfully.');
    }

    public function disable2fa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return $this->success(null, 'Two-factor authentication disabled.');
    }
}
