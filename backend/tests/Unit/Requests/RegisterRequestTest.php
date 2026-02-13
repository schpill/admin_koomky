<?php

declare(strict_types=1);

use App\Http\Requests\Auth\RegisterRequest;

it('authorizes any user', function () {
    $request = new RegisterRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires name, email, password', function () {
    $request = new RegisterRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'email', 'password']);
    expect($rules['name'])->toContain('required');
    expect($rules['email'])->toContain('required');
    expect($rules['password'])->toContain('required');
});

it('enforces email uniqueness', function () {
    $request = new RegisterRequest;
    $rules = $request->rules();

    $emailRules = implode('|', array_map(fn ($r) => is_string($r) ? $r : '', $rules['email']));
    expect($emailRules)->toContain('unique:users,email');
});

it('enforces password minimum 12 characters', function () {
    $request = new RegisterRequest;
    $rules = $request->rules();

    expect($rules['password'])->toContain('min:12');
    expect($rules['password'])->toContain('confirmed');
});

it('has custom error messages', function () {
    $request = new RegisterRequest;
    $messages = $request->messages();

    expect($messages)->toHaveKeys(['password.min', 'password.confirmed', 'email.unique']);
});
