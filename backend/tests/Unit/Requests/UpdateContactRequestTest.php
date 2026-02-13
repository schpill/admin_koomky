<?php

declare(strict_types=1);

use App\Http\Requests\Contact\UpdateContactRequest;

it('authorizes any user', function () {
    $request = new UpdateContactRequest;
    expect($request->authorize())->toBeTrue();
});

it('makes all fields nullable', function () {
    $request = new UpdateContactRequest;
    $rules = $request->rules();

    expect($rules['name'])->toContain('nullable');
    expect($rules['email'])->toContain('nullable');
    expect($rules['phone'])->toContain('nullable');
    expect($rules['position'])->toContain('nullable');
    expect($rules['is_primary'])->toContain('nullable');
});
