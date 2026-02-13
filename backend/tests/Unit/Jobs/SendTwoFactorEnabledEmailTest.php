<?php

declare(strict_types=1);

use App\Jobs\SendTwoFactorEnabledEmail;
use App\Mail\TwoFactorEnabledMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends two factor enabled email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $job = new SendTwoFactorEnabledEmail($user);
    $job->handle();

    Mail::assertSent(TwoFactorEnabledMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('is queued on emails queue', function () {
    $user = User::factory()->create();
    $job = new SendTwoFactorEnabledEmail($user);

    expect($job->queue)->toBe('emails');
});

it('implements ShouldQueue', function () {
    expect(new SendTwoFactorEnabledEmail(User::factory()->create()))
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});
