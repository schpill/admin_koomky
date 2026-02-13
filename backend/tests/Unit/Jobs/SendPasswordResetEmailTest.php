<?php

declare(strict_types=1);

use App\Jobs\SendPasswordResetEmail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends password reset email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $job = new SendPasswordResetEmail($user, 'test-token');
    $job->handle();

    Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('is queued on emails queue', function () {
    $user = User::factory()->create();
    $job = new SendPasswordResetEmail($user, 'test-token');

    expect($job->queue)->toBe('emails');
});

it('implements ShouldQueue', function () {
    expect(new SendPasswordResetEmail(User::factory()->create(), 'token'))
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('stores user and token', function () {
    $user = User::factory()->create();
    $job = new SendPasswordResetEmail($user, 'reset-token-123');

    expect($job->user->id)->toBe($user->id);
    expect($job->token)->toBe('reset-token-123');
});
