<?php

namespace App\Services\Sms;

use App\Services\PhoneValidationService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TwilioSmsDriver implements SmsService
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

        $sid = (string) ($this->config['account_sid'] ?? '');
        $token = (string) ($this->config['auth_token'] ?? '');
        $from = (string) ($this->config['from'] ?? '');

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json', [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ])
            ->throw()
            ->json();

        return [
            'message_id' => (string) ($response['sid'] ?? ''),
            'provider' => 'twilio',
        ];
    }

    public function getDeliveryStatus(string $messageId): array
    {
        return [
            'message_id' => $messageId,
            'status' => 'unknown',
            'provider' => 'twilio',
        ];
    }
}
