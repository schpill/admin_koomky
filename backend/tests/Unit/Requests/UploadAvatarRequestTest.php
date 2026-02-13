<?php

declare(strict_types=1);

use App\Http\Requests\UserSettings\UploadAvatarRequest;

it('authorizes any user', function () {
    $request = new UploadAvatarRequest;
    expect($request->authorize())->toBeTrue();
});

it('requires avatar file', function () {
    $request = new UploadAvatarRequest;
    $rules = $request->rules();

    expect($rules['avatar'])->toContain('required');
    expect($rules['avatar'])->toContain('file');
    expect($rules['avatar'])->toContain('image');
});

it('limits to jpeg and png', function () {
    $request = new UploadAvatarRequest;
    $rules = $request->rules();

    expect($rules['avatar'])->toContain('mimes:jpeg,png');
});

it('limits size to 2MB', function () {
    $request = new UploadAvatarRequest;
    $rules = $request->rules();

    expect($rules['avatar'])->toContain('max:2048');
});

it('has custom error messages', function () {
    $request = new UploadAvatarRequest;
    $messages = $request->messages();

    expect($messages)->toHaveKeys(['avatar.required', 'avatar.max', 'avatar.mimes']);
});
