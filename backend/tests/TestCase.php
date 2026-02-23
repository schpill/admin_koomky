<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Prevent actual mail rendering in all tests (avoids PHP 8.3 JIT/bytecode
        // compilation stack overflow with Blade templates). Tests that need to assert
        // on mail behaviour call Mail::fake() again in their own setUp() to get a fresh fake.
        Mail::fake();
    }
}
