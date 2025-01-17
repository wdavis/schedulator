<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        if (env('DB_DATABASE') === 'schedulator') {
            dd('DB_DATABASE is not set to a testing database');
        }
    }
}
