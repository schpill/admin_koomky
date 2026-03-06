<?php

use App\Services\DynamicContentValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('validator accepts a valid conditional block', function () {
    $result = app(DynamicContentValidatorService::class)->validate(
        '{{#if contact.first_name == "Jane"}}Bonjour{{else}}Salut{{/if}}'
    );

    expect($result['valid'])->toBeTrue()
        ->and($result['errors'])->toBe([]);
});

test('validator rejects unsupported variables and invalid nesting depth', function () {
    $result = app(DynamicContentValidatorService::class)->validate(
        '{{#if hacker.value == "x"}}A{{#if contact.first_name == "Jane"}}B{{#if client.department == "75"}}C{{/if}}{{/if}}{{/if}}'
    );

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});
