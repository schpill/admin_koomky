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
