<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

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
            $mailer = 'campaign_smtp';

            config([
                'mail.mailers.'.$mailer => [
                    'transport' => 'smtp',
                    'host' => (string) ($settings['smtp_host'] ?? 'localhost'),
                    'port' => (int) ($settings['smtp_port'] ?? 25),
                    'encryption' => $settings['encryption'] ?? null,
                    'username' => $settings['smtp_username'] ?? null,
                    'password' => $settings['smtp_password'] ?? null,
                    'timeout' => null,
                ],
            ]);

            Mail::purge($mailer);
            $this->configureFromAddress($settings);

            return $mailer;
        }

        if ($provider === 'ses') {
            $apiKey = $settings['api_key'] ?? null;
            $apiSecret = $settings['api_secret'] ?? null;

            if (is_string($apiKey) && $apiKey !== '' && is_string($apiSecret) && $apiSecret !== '') {
                $mailer = 'campaign_ses';

                config([
                    'mail.mailers.'.$mailer => [
                        'transport' => 'ses',
                        'key' => $apiKey,
                        'secret' => $apiSecret,
                        'region' => $this->resolveSesRegion($settings),
                    ],
                ]);

                Mail::purge($mailer);
                $this->configureFromAddress($settings);

                return $mailer;
            }
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

    /**
     * @param  array<string, mixed>  $settings
     */
    private function resolveSesRegion(array $settings): string
    {
        $region = $settings['api_region'] ?? null;

        if (is_string($region) && $region !== '') {
            return $region;
        }

        return (string) config('services.ses.region', 'us-east-1');
    }
}
