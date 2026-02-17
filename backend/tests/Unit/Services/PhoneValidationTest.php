<?php

use App\Services\PhoneValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('valid e164 numbers are accepted', function () {
    $service = app(PhoneValidationService::class);

    expect($service->isValidE164('+33612345678'))->toBeTrue();
    expect($service->isValidE164('+12025550123'))->toBeTrue();
});

test('local format and invalid numbers are rejected', function () {
    $service = app(PhoneValidationService::class);

    expect($service->isValidE164('06 12 34 56 78'))->toBeFalse();
    expect($service->isValidE164('123'))->toBeFalse();
    expect($service->isValidE164('+'))->toBeFalse();
});
