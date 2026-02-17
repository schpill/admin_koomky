<?php

namespace App\Services\Sms;

use App\Services\PhoneValidationService;
use InvalidArgumentException;

class SmsProviderManager
{
    public function __construct(private readonly PhoneValidationService $phoneValidationService) {}

    /**
     * @param  array<string, mixed>  $settings
     * @return array{message_id:string, provider:string}
     */
    public function send(array $settings, string $to, string $message): array
    {
        if (! $this->phoneValidationService->isValidE164($to)) {
            throw new InvalidArgumentException('Phone number must be a valid E.164 value.');
        }

        return $this->resolve($settings)->send($to, $message);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function resolve(array $settings): SmsService
    {
        $provider = strtolower((string) ($settings['provider'] ?? 'twilio'));

        return match ($provider) {
            'twilio' => new TwilioSmsDriver($this->phoneValidationService, $settings),
            'vonage' => new VonageSmsDriver($this->phoneValidationService, $settings),
            default => throw new InvalidArgumentException('Unsupported SMS provider: '.$provider),
        };
    }
}
