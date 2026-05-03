<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\Facades\Currency;

class FacadeTest extends TestCase
{
    public function test_facade_can_access_currency_methods()
    {
        $instance = \Fomvasss\Currency\Facades\Currency::getFacadeRoot();
        $this->assertTrue(method_exists($instance, 'convert'));
        $this->assertTrue(method_exists($instance, 'getRate'));
        $this->assertTrue(method_exists($instance, 'format'));
    }

    public function test_facade_resolves_to_currency_instance()
    {
        $instance = Currency::getFacadeRoot();
        $this->assertInstanceOf(\Fomvasss\Currency\Currency::class, $instance);
    }
}
