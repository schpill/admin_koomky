<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;

it('authorizes any user', function () {
    $request = new LoginRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires email', function () {
    $request = new LoginRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('required');
});

it('requires password', function () {
    $request = new LoginRequest;
    $rules = $request->rules();

    expect($rules['password'])->toContain('required');
});

it('validates email format', function () {
    $request = new LoginRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('email');
});

it('has custom error messages', function () {
    $request = new LoginRequest;
    $messages = $request->messages();

    expect($messages)->toHaveKey('email.required');
    expect($messages)->toHaveKey('password.required');
});
