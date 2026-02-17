<?php

use App\Models\User;
use App\Services\MailConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('mail config service configures smtp mailer', function () {
    $user = User::factory()->create([
        'email_settings' => [
            'provider' => 'smtp',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_username' => 'mailer@example.test',
            'smtp_password' => 'secret',
            'encryption' => 'tls',
            'from_email' => 'no-reply@example.test',
            'from_name' => 'Acme',
        ],
    ]);

    $service = app(MailConfigService::class);
    $mailer = $service->configureForUser($user);

    expect($mailer)->toBe('campaign_smtp');
    expect(config('mail.mailers.campaign_smtp.host'))->toBe('smtp.example.test');
    expect(config('mail.from.address'))->toBe('no-reply@example.test');
});

test('mail config service configures api based provider and fallback', function () {
    $user = User::factory()->create([
        'email_settings' => [
            'provider' => 'mailgun',
            'from_email' => 'mailer@example.test',
            'from_name' => 'Mailer',
        ],
    ]);

    $service = app(MailConfigService::class);
    $mailer = $service->configureForUser($user);

    expect($mailer)->toBe('mailgun');

    $fallbackUser = User::factory()->create(['email_settings' => null]);
    expect($service->configureForUser($fallbackUser))->toBe((string) config('mail.default'));
});

test('mail config service configures ses api credentials per user', function () {
    config([
        'services.ses.key' => 'global-key',
        'services.ses.secret' => 'global-secret',
        'services.ses.region' => 'eu-west-1',
    ]);

    $user = User::factory()->create([
        'email_settings' => [
            'provider' => 'ses',
            'api_key' => 'user-key',
            'api_secret' => 'user-secret',
            'api_region' => 'us-west-2',
            'from_email' => 'ses@example.test',
            'from_name' => 'SES Sender',
        ],
    ]);

    $service = app(MailConfigService::class);
    $mailer = $service->configureForUser($user);

    expect($mailer)->toBe('campaign_ses');
    expect(config('mail.mailers.campaign_ses.transport'))->toBe('ses');
    expect(config('mail.mailers.campaign_ses.key'))->toBe('user-key');
    expect(config('mail.mailers.campaign_ses.secret'))->toBe('user-secret');
    expect(config('mail.mailers.campaign_ses.region'))->toBe('us-west-2');
    expect(config('mail.from.address'))->toBe('ses@example.test');
    expect(config('mail.from.name'))->toBe('SES Sender');
});
