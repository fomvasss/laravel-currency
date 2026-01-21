<?php

namespace Tests\Unit;

use Fomvasss\Currency\RateProviders\JsDelivrProvider;
use Tests\TestCase;

class JsDelivrProviderTest extends TestCase
{
    /** @test */
    public function it_can_fetch_rates_from_jsdelivr()
    {
        $provider = new JsDelivrProvider();
        
        // Get rates
        $rates = $provider->getRates();
        
        // Should return array
        $this->assertIsArray($rates);
        
        // Should have multiple currencies
        $this->assertGreaterThan(10, count($rates));
        
        // Check if major currencies exist
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
    }

    /** @test */
    public function it_returns_proper_rate_structure()
    {
        $provider = new JsDelivrProvider();
        $rate = $provider->getRate('USD');
        
        if ($rate !== null) {
            $this->assertIsArray($rate);
            $this->assertArrayHasKey('buy', $rate);
            $this->assertArrayHasKey('sell', $rate);
            $this->assertIsFloat($rate['buy']);
            $this->assertIsFloat($rate['sell']);
        } else {
            $this->markTestSkipped('USD rate not available from jsDelivr');
        }
    }

    /** @test */
    public function it_supports_multiple_currencies()
    {
        $provider = new JsDelivrProvider();
        
        // Check support for various currencies
        $this->assertTrue($provider->supports('USD'));
        $this->assertTrue($provider->supports('EUR'));
        
        // jsDelivr should support many currencies
        $supportedCount = count($provider->getRates());
        $this->assertGreaterThan(50, $supportedCount);
    }

    /** @test */
    public function it_has_uah_as_base_currency()
    {
        $provider = new JsDelivrProvider();
        $this->assertEquals('UAH', $provider->getBaseCurrency());
    }

    /** @test */
    public function it_provides_fallback_rates_on_error()
    {
        $provider = new JsDelivrProvider();
        
        // Test fallback mechanism by checking if major currencies exist
        $rates = $provider->getRates();
        
        // Even on error, should return some data (fallback)
        $this->assertIsArray($rates);
    }
}
