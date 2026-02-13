<?php

declare(strict_types=1);

use App\Mail\WelcomeMail;
use App\Models\User;

it('has correct subject', function () {
    $user = User::factory()->create();
    $mail = new WelcomeMail($user);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Welcome to');
});

it('uses welcome view', function () {
    $user = User::factory()->create();
    $mail = new WelcomeMail($user);

    $content = $mail->content();

    expect($content->view)->toBe('emails.welcome');
});

it('passes user and setup url to view', function () {
    $user = User::factory()->create();
    $mail = new WelcomeMail($user);

    $content = $mail->content();

    expect($content->with)->toHaveKeys(['user', 'setupUrl', 'appName']);
});

it('has no attachments', function () {
    $user = User::factory()->create();
    $mail = new WelcomeMail($user);

    expect($mail->attachments())->toBeEmpty();
});
