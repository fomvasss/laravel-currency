<?php

namespace Fomvasss\Currency\Tests;

class ServiceProviderTest extends TestCase
{
    public function test_unknown_default_provider_throws_exception()
    {
        config(['currency.default_provider' => 'unknown_provider']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'unknown_provider' is not configured and class does not exist");

        app('currency');
    }
}
