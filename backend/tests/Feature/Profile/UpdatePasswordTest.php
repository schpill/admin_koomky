<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('password update changes the password and revokes other tokens', function () {
    $user = User::factory()->create([
        'password' => bcrypt('CurrentPassword123!'),
    ]);

    $current = $user->createToken('current', ['access']);
    $other = $user->createToken('other', ['access']);

    $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
        ->postJson('/api/v1/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertOk();

    expect(Hash::check('NewPassword123!', (string) $user->fresh()?->password))->toBeTrue();
    expect(PersonalAccessToken::query()->find($current->accessToken->id))->not->toBeNull();
    expect(PersonalAccessToken::query()->find($other->accessToken->id))->toBeNull();
});

test('password update rejects an invalid current password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('CurrentPassword123!'),
    ]);

    $token = $user->createToken('current', ['access']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/profile/password', [
            'current_password' => 'WrongPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');
});
