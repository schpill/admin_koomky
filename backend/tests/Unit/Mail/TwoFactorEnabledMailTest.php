<?php

declare(strict_types=1);

use App\Mail\TwoFactorEnabledMail;
use App\Models\User;

it('has correct subject', function () {
    $user = User::factory()->create();
    $mail = new TwoFactorEnabledMail($user);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Two-Factor Authentication Enabled');
});

it('uses two factor enabled view', function () {
    $user = User::factory()->create();
    $mail = new TwoFactorEnabledMail($user);

    $content = $mail->content();

    expect($content->view)->toBe('emails.two-factor-enabled');
});

it('passes user and app name to view', function () {
    $user = User::factory()->create();
    $mail = new TwoFactorEnabledMail($user);

    $content = $mail->content();

    expect($content->with)->toHaveKeys(['user', 'appName']);
});

it('has no attachments', function () {
    $user = User::factory()->create();
    $mail = new TwoFactorEnabledMail($user);

    expect($mail->attachments())->toBeEmpty();
});
