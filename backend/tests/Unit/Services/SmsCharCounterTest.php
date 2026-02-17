<?php

use App\Services\SmsCharCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('ascii 160 chars uses one segment', function () {
    $counter = app(SmsCharCounter::class);

    $result = $counter->count(str_repeat('a', 160));

    expect($result['segments'])->toBe(1);
});

test('ascii 161 chars uses two segments', function () {
    $counter = app(SmsCharCounter::class);

    $result = $counter->count(str_repeat('a', 161));

    expect($result['segments'])->toBe(2);
});

test('unicode 70 chars uses one segment', function () {
    $counter = app(SmsCharCounter::class);

    $result = $counter->count(str_repeat('é', 70));

    expect($result['segments'])->toBe(1);
});

test('mixed content is treated as unicode segments', function () {
    $counter = app(SmsCharCounter::class);

    $result = $counter->count('Hello é'.str_repeat('a', 80));

    expect($result['encoding'])->toBe('unicode');
    expect($result['segments'])->toBeGreaterThan(1);
});
