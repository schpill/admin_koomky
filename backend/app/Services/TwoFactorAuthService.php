<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

final readonly class TwoFactorAuthService
{
    public function __construct(
        private Google2FA $google2FA
    ) {
    }

    /**
     * Enable 2FA for a user.
     *
     * @return array{secret: string, qr_code_url: string, recovery_codes: array<string>}
     */
    public function enable(User $user): array
    {
        $secret = $this->google2FA->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodesArray();

        // Store encrypted secret temporarily (not enabled until verified)
        Cache::put(
            "2fa:setup:{$user->id}",
            [
                'secret' => $secret,
                'recovery_codes' => encrypt(json_encode($recoveryCodes)),
            ],
            now()->addMinutes(15)
        );

        $qrCodeUrl = $this->google2FA->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Verify 2FA code and finalize setup.
     */
    public function verify(User $user, string $code): bool
    {
        $setupData = Cache::get("2fa:setup:{$user->id}");

        if (!$setupData) {
            throw new \Exception('Two-factor authentication setup expired. Please try again.');
        }

        $secret = $setupData['secret'];

        if (!$this->google2FA->verifyKey($secret, $code)) {
            // Check recovery codes
            $recoveryCodes = json_decode(decrypt($setupData['recovery_codes']), true);

            if (!in_array($code, $recoveryCodes, true)) {
                return false;
            }
        }

        // Store final data
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = $setupData['recovery_codes'];
        $user->two_factor_enabled = true;
        $user->two_factor_enabled_at = now();
        $user->save();

        // Clear cache
        Cache::forget("2fa:setup:{$user->id}");

        return true;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_enabled = false;
        $user->two_factor_enabled_at = null;
        $user->save();
    }

    /**
     * Verify a 2FA code during login.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->hasTwoFactorEnabled()) {
            return true;
        }

        $secret = decrypt($user->two_factor_secret);

        // Verify TOTP code
        if ($this->google2FA->verifyKey($secret, $code)) {
            return true;
        }

        // Check recovery codes
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (in_array($code, $recoveryCodes, true)) {
            // Remove used recovery code
            $recoveryCodes = array_filter($recoveryCodes, fn ($c) => $c !== $code);
            $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));
            $user->save();

            return true;
        }

        return false;
    }

    /**
     * Confirm 2FA during login and generate tokens.
     *
     * @return array{token: string, refresh_token: string, expires_in: int}
     */
    public function confirm(string $userId, string $code): array
    {
        $user = User::findOrFail($userId);

        if (!$this->verifyCode($user, $code)) {
            throw new \Exception('Invalid two-factor authentication code.');
        }

        /** @var AuthService */
        $authService = app(AuthService::class);
        return $authService->generateTokens($user);
    }

    /**
     * Generate new recovery codes.
     *
     * @return array<string>
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodesArray();
        $user->two_factor_recovery_codes = encrypt(json_encode($codes));
        $user->save();

        return $codes;
    }

    /**
     * Generate an array of recovery codes.
     *
     * @return array<string>
     */
    private function generateRecoveryCodesArray(): array
    {
        return collect(range(1, 8))
            ->map(fn () => $this->generateRecoveryCode())
            ->toArray();
    }

    /**
     * Generate a single recovery code.
     */
    private function generateRecoveryCode(): string
    {
        return strtoupper(bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4)));
    }
}
