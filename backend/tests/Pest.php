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
        $key = 'base64:dGVzdGluZ2tleWZvcl9waHB1bml0X2FuZF9wZXN0XyE=';

        if (! config('app.key')) {
            putenv("APP_KEY={$key}");
            config(['app.key' => $key]);
            app()->forgetInstance('encrypter');
            app()->forgetInstance(\Illuminate\Contracts\Encryption\Encrypter::class);
        }
    });
