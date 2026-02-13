<?php

declare(strict_types=1);

use App\Http\Requests\Auth\ForgotPasswordRequest;

it('authorizes any user', function () {
    $request = new ForgotPasswordRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires email', function () {
    $request = new ForgotPasswordRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('email');
});
