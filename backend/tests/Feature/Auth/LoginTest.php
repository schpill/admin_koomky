<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any existing lockout data
        Redis::flushall();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes',
                ],
                'meta' => [
                    'token' => [
                        'access_token',
                        'refresh_token',
                        'expires_in',
                    ],
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'message' => 'The provided credentials are incorrect.',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPass123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_email_and_password_fields_are_required(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'WrongPass123!',
            ]);
        }

        // 6th attempt should be locked
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'CorrectPass123!',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'error' => [
                    'message',
                    'retry_after',
                ],
            ]);
    }

    public function test_lockout_duration_is_15_minutes(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPass123!'),
        ]);

        // Trigger lockout
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'WrongPass123!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'CorrectPass123!',
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('retry_after', $data['error']);
        $this->assertGreaterThan(0, $data['error']['retry_after']);
        $this->assertLessThanOrEqual(900, $data['error']['retry_after']); // 15 minutes = 900 seconds
    }
}
