<?php

use App\Services\PhoneValidationService;
use App\Services\Sms\SmsProviderManager;
use App\Services\Sms\TwilioSmsDriver;
use App\Services\Sms\VonageSmsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('twilio driver sends sms', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'twilio-message-123'], 201),
    ]);

    $driver = new TwilioSmsDriver(app(PhoneValidationService::class), [
        'account_sid' => 'AC123',
        'auth_token' => 'token',
        'from' => '+33123456789',
    ]);

    $response = $driver->send('+33612345678', 'Hello from Twilio');

    expect($response['message_id'])->toBe('twilio-message-123');
});

test('vonage driver sends sms', function () {
    Http::fake([
        'https://rest.nexmo.com/*' => Http::response(['messages' => [['message-id' => 'vonage-msg-456']]], 200),
    ]);

    $driver = new VonageSmsDriver(app(PhoneValidationService::class), [
        'api_key' => 'key',
        'api_secret' => 'secret',
        'from' => 'KOOMKY',
    ]);

    $response = $driver->send('+33612345678', 'Hello from Vonage');

    expect($response['message_id'])->toBe('vonage-msg-456');
});

test('invalid phone is rejected by sms provider manager', function () {
    $manager = new SmsProviderManager(app(PhoneValidationService::class));

    expect(fn () => $manager->send([
        'provider' => 'twilio',
        'account_sid' => 'AC123',
        'auth_token' => 'token',
        'from' => '+33123456789',
    ], '06 12 34 56 78', 'Invalid format'))
        ->toThrow(InvalidArgumentException::class);
});
