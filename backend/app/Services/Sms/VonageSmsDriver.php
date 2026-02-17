<?php

namespace App\Services\Sms;

use App\Services\PhoneValidationService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class VonageSmsDriver implements SmsService
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly PhoneValidationService $phoneValidationService,
        private readonly array $config
    ) {}

    public function send(string $to, string $message): array
    {
        if (! $this->phoneValidationService->isValidE164($to)) {
            throw new InvalidArgumentException('Phone number must be a valid E.164 value.');
        }

        $response = Http::asForm()
            ->post('https://rest.nexmo.com/sms/json', [
                'api_key' => (string) ($this->config['api_key'] ?? ''),
                'api_secret' => (string) ($this->config['api_secret'] ?? ''),
                'from' => (string) ($this->config['from'] ?? 'KOOMKY'),
                'to' => ltrim($to, '+'),
                'text' => $message,
            ])
            ->throw()
            ->json();

        $first = $response['messages'][0] ?? [];

        return [
            'message_id' => (string) ($first['message-id'] ?? ''),
            'provider' => 'vonage',
        ];
    }

    public function getDeliveryStatus(string $messageId): array
    {
        return [
            'message_id' => $messageId,
            'status' => 'unknown',
            'provider' => 'vonage',
        ];
    }
}
