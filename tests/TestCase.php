<?php

declare(strict_types=1);

namespace XbNz\Gemini\Tests;

use NunoMaduro\Collision\Provider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Dotenv\Dotenv;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new Provider())->register();
        (new Dotenv())->load(__DIR__.'/../.env.testing');
    }
}
