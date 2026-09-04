<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function withCsrf(): static
    {
        $token = Str::random(40);

        return $this->withSession(['_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token);
    }
}
