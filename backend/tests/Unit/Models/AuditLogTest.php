<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('audit log belongs to a user', function () {
    $user = User::factory()->create();

    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => 'auth.login',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'TestAgent/1.0',
    ]);

    expect($log->user)->toBeInstanceOf(User::class);
    expect($log->user->id)->toBe($user->id);
});

test('audit log casts metadata to array', function () {
    $user = User::factory()->create();

    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => 'auth.login',
        'metadata' => ['browser' => 'Chrome', 'os' => 'Linux'],
    ]);

    $log->refresh();
    expect($log->metadata)->toBeArray();
    expect($log->metadata['browser'])->toBe('Chrome');
});

test('audit log uses uuid as primary key', function () {
    $user = User::factory()->create();

    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => 'test.event',
    ]);

    expect($log->id)->toBeString();
    expect(strlen($log->id))->toBe(36);
});

test('audit log fillable attributes are set correctly', function () {
    $fillable = (new AuditLog())->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('event');
    expect($fillable)->toContain('ip_address');
    expect($fillable)->toContain('user_agent');
    expect($fillable)->toContain('metadata');
});

test('audit log can be created without optional fields', function () {
    $user = User::factory()->create();

    $log = AuditLog::create([
        'user_id' => $user->id,
        'event' => 'test.event',
    ]);

    expect($log->ip_address)->toBeNull();
    expect($log->user_agent)->toBeNull();
    expect($log->metadata)->toBeNull();
});
