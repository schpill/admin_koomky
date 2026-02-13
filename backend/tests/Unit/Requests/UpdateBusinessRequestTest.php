<?php

declare(strict_types=1);

use App\Http\Requests\UserSettings\UpdateBusinessRequest;

it('authorizes any user', function () {
    $request = new UpdateBusinessRequest;
    expect($request->authorize())->toBeTrue();
});

it('uses sometimes rule for all fields', function () {
    $request = new UpdateBusinessRequest;
    $rules = $request->rules();

    foreach (['business_name', 'business_address', 'siret', 'ape_code', 'vat_number', 'default_payment_terms', 'invoice_footer'] as $field) {
        expect($rules[$field])->toContain('sometimes');
    }
});

it('enforces exact siret length of 14', function () {
    $request = new UpdateBusinessRequest;
    $rules = $request->rules();

    expect($rules['siret'])->toContain('size:14');
});

it('enforces exact ape_code length of 6', function () {
    $request = new UpdateBusinessRequest;
    $rules = $request->rules();

    expect($rules['ape_code'])->toContain('size:6');
});

it('validates payment terms range', function () {
    $request = new UpdateBusinessRequest;
    $rules = $request->rules();

    expect($rules['default_payment_terms'])->toContain('integer');
    expect($rules['default_payment_terms'])->toContain('min:1');
    expect($rules['default_payment_terms'])->toContain('max:365');
});
