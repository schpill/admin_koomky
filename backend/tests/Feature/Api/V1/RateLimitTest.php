<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('auth routes have rate limiting', function () {
    $user = User::factory()->create();

    // Clear limiter before test
    RateLimiter::clear('api_auth:'.request()->ip());

    // We try until we get a 429
    $throttled = false;
    for ($i = 0; $i < 15; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        if ($response->status() === 429) {
            $throttled = true;
            break;
        }
    }

    expect($throttled)->toBeTrue();
});
