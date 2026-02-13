<?php

declare(strict_types=1);

use App\Http\Requests\Client\CreateClientRequest;

it('authorizes any user', function () {
    $request = new CreateClientRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires name', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('string');
    expect($rules['name'])->toContain('max:255');
});

it('has nullable email, phone, company fields', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['email'])->toContain('nullable');
    expect($rules['phone'])->toContain('nullable');
    expect($rules['company'])->toContain('nullable');
});

it('validates website as url', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['website'])->toContain('url');
});

it('limits billing_address to 1000 chars', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['billing_address'])->toContain('max:1000');
});

it('limits notes to 5000 chars', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['notes'])->toContain('max:5000');
});

it('restricts status to active or archived', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['status'])->toContain('in:active,archived');
});

it('accepts tags as array of strings', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules['tags'])->toContain('array');
    expect($rules['tags.*'])->toContain('string');
});

it('validates contacts nested structure', function () {
    $request = new CreateClientRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'contacts',
        'contacts.*.name',
        'contacts.*.email',
        'contacts.*.phone',
        'contacts.*.position',
        'contacts.*.is_primary',
    ]);
});
