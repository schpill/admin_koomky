<?php

declare(strict_types=1);

use App\Http\Requests\Contact\CreateContactRequest;

it('authorizes any user', function () {
    $request = new CreateContactRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires name', function () {
    $request = new CreateContactRequest;
    $rules = $request->rules();

    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('string');
    expect($rules['name'])->toContain('max:255');
});

it('has nullable optional fields', function () {
    $request = new CreateContactRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('nullable');
    expect($rules['phone'])->toContain('nullable');
    expect($rules['position'])->toContain('nullable');
    expect($rules['is_primary'])->toContain('nullable');
});

it('validates email format', function () {
    $request = new CreateContactRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('email');
});

it('validates is_primary as boolean', function () {
    $request = new CreateContactRequest;
    $rules = $request->rules();

    expect($rules['is_primary'])->toContain('boolean');
});
