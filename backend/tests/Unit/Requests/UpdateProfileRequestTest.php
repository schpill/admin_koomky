<?php

declare(strict_types=1);

use App\Http\Requests\UserSettings\UpdateProfileRequest;

it('authorizes any user', function () {
    $request = new UpdateProfileRequest;
    expect($request->authorize())->toBeTrue();
});

it('uses sometimes rule for all fields', function () {
    $request = new UpdateProfileRequest;
    $rules = $request->rules();

    foreach (['name', 'email', 'business_name', 'business_address', 'siret', 'ape_code', 'vat_number', 'default_payment_terms', 'invoice_footer'] as $field) {
        expect($rules[$field])->toContain('sometimes');
    }
});

it('validates payment terms range', function () {
    $request = new UpdateProfileRequest;
    $rules = $request->rules();

    expect($rules['default_payment_terms'])->toContain('integer');
    expect($rules['default_payment_terms'])->toContain('min:1');
    expect($rules['default_payment_terms'])->toContain('max:365');
});

it('validates siret max length', function () {
    $request = new UpdateProfileRequest;
    $rules = $request->rules();

    expect($rules['siret'])->toContain('max:14');
});
