<?php

declare(strict_types=1);

use App\Enums\AuthEventType;
use App\Models\AuditLog;
use App\Models\User;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => AuthEventType::LOGIN,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    expect($log->user)->toBeInstanceOf(User::class);
    expect($log->user->id)->toBe($user->id);
});

it('casts metadata to array', function () {
    $user = User::factory()->create();
    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => AuthEventType::LOGIN,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'metadata' => ['browser' => 'Chrome'],
    ]);

    expect($log->metadata)->toBeArray();
    expect($log->metadata['browser'])->toBe('Chrome');
});

it('has no updated_at column', function () {
    expect(AuditLog::UPDATED_AT)->toBeNull();
});

it('stores event as string enum value', function () {
    $user = User::factory()->create();
    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => AuthEventType::LOGOUT,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    expect($log->event)->not->toBeNull();
});

it('uses UUID as primary key', function () {
    $user = User::factory()->create();
    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => AuthEventType::LOGIN,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    expect($log->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
