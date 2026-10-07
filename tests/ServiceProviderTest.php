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

    public function test_runtime_switches_do_not_outlive_the_request()
    {
        app('currency')->setBaseCurrency('USD');
        $this->assertSame('USD', app('currency')->getBaseCurrency());

        // what Octane and the queue worker do between requests/jobs
        $this->app->forgetScopedInstances();

        $this->assertNotSame('USD', app('currency')->getBaseCurrency());
    }
}
