<?php

namespace App\Services;

use App\Models\User;

class MailConfigService
{
    public function configureForUser(User $user): string
    {
        /** @var array<string, mixed>|null $settings */
        $settings = $user->email_settings;

        if (! is_array($settings)) {
            return (string) config('mail.default');
        }

        $provider = strtolower((string) ($settings['provider'] ?? ''));

        if ($provider === 'smtp') {
            config([
                'mail.mailers.campaign_smtp' => [
                    'transport' => 'smtp',
                    'host' => (string) ($settings['smtp_host'] ?? 'localhost'),
                    'port' => (int) ($settings['smtp_port'] ?? 25),
                    'encryption' => $settings['encryption'] ?? null,
                    'username' => $settings['smtp_username'] ?? null,
                    'password' => $settings['smtp_password'] ?? null,
                    'timeout' => null,
                ],
            ]);

            $this->configureFromAddress($settings);

            return 'campaign_smtp';
        }

        if (in_array($provider, ['mailgun', 'ses', 'postmark', 'sendmail'], true)) {
            $this->configureFromAddress($settings);

            return $provider;
        }

        return (string) config('mail.default');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function configureFromAddress(array $settings): void
    {
        $fromAddress = $settings['from_email'] ?? null;
        $fromName = $settings['from_name'] ?? null;

        if (is_string($fromAddress) && $fromAddress !== '') {
            config(['mail.from.address' => $fromAddress]);
        }

        if (is_string($fromName) && $fromName !== '') {
            config(['mail.from.name' => $fromName]);
        }
    }
}
