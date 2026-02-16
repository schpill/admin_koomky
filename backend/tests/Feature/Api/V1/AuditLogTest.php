<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login event is logged in audit logs', function () {
    $user = User::factory()->create([
        'password' => bcrypt($password = 'Password123!'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'event' => 'auth.login',
    ]);
});
