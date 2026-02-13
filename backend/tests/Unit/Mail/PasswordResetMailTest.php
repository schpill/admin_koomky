<?php

declare(strict_types=1);

use App\Mail\PasswordResetMail;
use App\Models\User;

it('has correct subject', function () {
    $user = User::factory()->create();
    $mail = new PasswordResetMail($user, 'test-token');

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Reset Your Password');
});

it('uses password reset view', function () {
    $user = User::factory()->create();
    $mail = new PasswordResetMail($user, 'test-token');

    $content = $mail->content();

    expect($content->view)->toBe('emails.password-reset');
});

it('passes reset url with token and email', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $mail = new PasswordResetMail($user, 'my-token');

    $content = $mail->content();

    expect($content->with['resetUrl'])->toContain('my-token');
    expect($content->with['resetUrl'])->toContain(urlencode('test@example.com'));
    expect($content->with['expiresIn'])->toBe(60);
});

it('has no attachments', function () {
    $user = User::factory()->create();
    $mail = new PasswordResetMail($user, 'token');

    expect($mail->attachments())->toBeEmpty();
});
