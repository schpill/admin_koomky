<?php

declare(strict_types=1);

use App\Exceptions\Handler;

it('does not flash sensitive fields', function () {
    $handler = app(Handler::class);

    $reflection = new ReflectionProperty($handler, 'dontFlash');
    $reflection->setAccessible(true);
    $dontFlash = $reflection->getValue($handler);

    expect($dontFlash)->toContain('current_password');
    expect($dontFlash)->toContain('password');
    expect($dontFlash)->toContain('password_confirmation');
});
