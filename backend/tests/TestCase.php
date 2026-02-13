<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $key = 'base64:dGVzdGluZ2tleWZvcl9waHB1bml0X2FuZF9wZXN0XyE=';

        if (! config('app.key')) {
            putenv("APP_KEY={$key}");
            config(['app.key' => $key]);
            app()->forgetInstance('encrypter');
            app()->forgetInstance(\Illuminate\Contracts\Encryption\Encrypter::class);
        }
    }

    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Additional cleanup if needed
    }
}
