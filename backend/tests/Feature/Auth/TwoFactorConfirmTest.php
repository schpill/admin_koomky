<?php

declare(strict_types=1);

it('validates code is required for 2FA confirm', function () {
    $this->postJson('/api/v1/auth/2fa/confirm', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('returns error for invalid 2FA code without session', function () {
    $this->postJson('/api/v1/auth/2fa/confirm', [
        'code' => '123456',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_2fa_code');
});

it('validates remember field is boolean', function () {
    $this->postJson('/api/v1/auth/2fa/confirm', [
        'code' => '123456',
        'remember' => 'not-boolean',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['remember']);
});
