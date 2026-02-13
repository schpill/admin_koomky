<?php

declare(strict_types=1);

use App\Http\Requests\Client\UpdateClientRequest;

it('authorizes any user', function () {
    $request = new UpdateClientRequest;
    expect($request->authorize())->toBeTrue();
});

it('makes name optional with sometimes rule', function () {
    $request = new UpdateClientRequest;
    $rules = $request->rules();

    expect($rules['name'])->toContain('sometimes');
    expect($rules['name'])->toContain('required');
});

it('has same field constraints as create request', function () {
    $request = new UpdateClientRequest;
    $rules = $request->rules();

    expect($rules['website'])->toContain('url');
    expect($rules['billing_address'])->toContain('max:1000');
    expect($rules['notes'])->toContain('max:5000');
    expect($rules['status'])->toContain('in:active,archived');
});

it('accepts tags and contacts arrays', function () {
    $request = new UpdateClientRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['tags', 'tags.*', 'contacts', 'contacts.*.name']);
});
