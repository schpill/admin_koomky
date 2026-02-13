<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

uses(TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit')
    ->beforeEach(function () {
        // Setup before each test
    })
    ->afterEach(function () {
        // Cleanup after each test
    });
