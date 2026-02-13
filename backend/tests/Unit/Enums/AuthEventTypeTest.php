<?php

declare(strict_types=1);

use App\Enums\AuthEventType;

it('has all expected enum cases', function () {
    $cases = AuthEventType::cases();

    expect($cases)->toHaveCount(7);
    expect(AuthEventType::LOGIN->value)->toBe('login');
    expect(AuthEventType::LOGOUT->value)->toBe('logout');
    expect(AuthEventType::FAILED_LOGIN->value)->toBe('failed_login');
    expect(AuthEventType::PASSWORD_RESET->value)->toBe('password_reset');
    expect(AuthEventType::TWO_FACTOR_ENABLED->value)->toBe('two_factor_enabled');
    expect(AuthEventType::TWO_FACTOR_DISABLED->value)->toBe('two_factor_disabled');
    expect(AuthEventType::PASSWORD_CHANGED->value)->toBe('password_changed');
});

it('is a backed string enum', function () {
    expect(AuthEventType::LOGIN)->toBeInstanceOf(\BackedEnum::class);
});
