<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('user is locked out after too many failed login attempts', function () {
    Event::fake([Lockout::class]);
    $user = User::factory()->create();

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    // 6th attempt should be locked out
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
    $response->assertJsonPath('message', fn ($message) => str_contains($message, '900 seconds') || str_contains($message, '899 seconds'));
    Event::assertDispatched(Lockout::class);
});
