<?php

declare(strict_types=1);

use App\Http\Requests\Auth\ResetPasswordRequest;

it('authorizes any user', function () {
    $request = new ResetPasswordRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires token, email and password', function () {
    $request = new ResetPasswordRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['token', 'email', 'password']);
    expect($rules['token'])->toContain('required');
    expect($rules['email'])->toContain('required');
    expect($rules['password'])->toContain('required');
});

it('enforces password minimum 12 characters and confirmation', function () {
    $request = new ResetPasswordRequest;
    $rules = $request->rules();

    expect($rules['password'])->toContain('min:12');
    expect($rules['password'])->toContain('confirmed');
});
