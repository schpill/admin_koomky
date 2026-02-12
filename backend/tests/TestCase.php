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
        // Additional setup if needed
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
