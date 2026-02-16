<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('users:create asks for email when no argument is provided', function () {
    $this->artisan('users:create')
        ->expectsQuestion('Email address', 'owner@example.com')
        ->expectsOutputToContain('User created successfully.')
        ->expectsOutputToContain('Password:')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'owner@example.com')->first();

    expect($user)->not()->toBeNull();
    expect($user?->name)->toBe('Owner');
});

test('users:create generates a strong password and stores it hashed', function () {
    $exitCode = Artisan::call('users:create', [
        'email' => 'jane.doe+crm@example.com',
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('User created successfully.');

    preg_match('/Password:\s+([^\r\n]+)/', $output, $matches);

    expect($matches)->toHaveKey(1);

    $plainPassword = trim($matches[1]);
    $user = User::query()->where('email', 'jane.doe+crm@example.com')->firstOrFail();

    expect(strlen($plainPassword))->toBeGreaterThanOrEqual(8);
    expect(preg_match('/[a-z]/', $plainPassword))->toBe(1);
    expect(preg_match('/[A-Z]/', $plainPassword))->toBe(1);
    expect(preg_match('/\d/', $plainPassword))->toBe(1);
    expect(preg_match('/[^A-Za-z0-9]/', $plainPassword))->toBe(1);
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('users:create fails when email is invalid or already used', function () {
    $invalidExitCode = Artisan::call('users:create', [
        'email' => 'invalid-email',
    ]);

    expect($invalidExitCode)->toBe(1);
    expect(Artisan::output())->toContain('must be a valid email address');

    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $duplicateExitCode = Artisan::call('users:create', [
        'email' => 'existing@example.com',
    ]);

    expect($duplicateExitCode)->toBe(1);
    expect(Artisan::output())->toContain('already been taken');
});
