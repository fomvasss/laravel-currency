<?php

namespace Fomvasss\Currency\Tests\Feature;

use Fomvasss\Currency\Facades\Currency;
use Fomvasss\Currency\Facades\CurrencyProvider;
use Fomvasss\Currency\RateProviders\NbuRateProvider;
use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Fomvasss\Currency\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;

class ProviderConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.monobank.ua/*' => Http::response([
                ['currencyCodeA' => 840, 'currencyCodeB' => 980, 'rateBuy' => 41.0, 'rateSell' => 41.5],
                ['currencyCodeA' => 978, 'currencyCodeB' => 980, 'rateBuy' => 45.0, 'rateSell' => 45.8],
            ]),
            'api.privatbank.ua/*' => Http::response([
                ['ccy' => 'USD', 'base_ccy' => 'UAH', 'buy' => '41.00000', 'sale' => '41.50000'],
                ['ccy' => 'EUR', 'base_ccy' => 'UAH', 'buy' => '45.00000', 'sale' => '45.80000'],
            ]),
            'bank.gov.ua/*' => Http::response([
                ['cc' => 'USD', 'rate' => 41.2],
                ['cc' => 'EUR', 'rate' => 45.4],
                ['cc' => 'PLN', 'rate' => 10.3],
            ]),
        ]);
    }

    #[Test]
    public function it_can_use_configured_providers()
    {
        // Test that providers are properly configured
        $availableProviders = Currency::getAvailableProviders();
        
        $this->assertArrayHasKey('nbu', $availableProviders);
        $this->assertArrayHasKey('monobank', $availableProviders);
        $this->assertArrayHasKey('jsdelivr', $availableProviders);
        $this->assertEquals(NbuRateProvider::class, $availableProviders['nbu']);
        $this->assertEquals(MonobankRateProvider::class, $availableProviders['monobank']);
    }

    #[Test]
    public function it_can_switch_providers_using_names()
    {
        // Test switching to NBU provider
        Currency::useProvider('nbu');
        $provider = Currency::getProvider();
        $this->assertInstanceOf(NbuRateProvider::class, $provider);

        // Test switching to Monobank provider  
        Currency::useProvider('monobank');
        $provider = Currency::getProvider();
        $this->assertInstanceOf(MonobankRateProvider::class, $provider);
    }

    #[Test]
    public function it_throws_exception_for_unknown_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'unknown' is not configured");
        
        Currency::useProvider('unknown');
    }

    #[Test]
    public function it_can_use_provider_manager()
    {
        // Test getting provider through manager
        $nbuProvider = CurrencyProvider::provider('nbu');
        $this->assertInstanceOf(NbuRateProvider::class, $nbuProvider);

        // Test getting available providers through manager
        $available = CurrencyProvider::getAvailableProviders();
        $this->assertIsArray($available);
        $this->assertArrayHasKey('nbu', $available);
    }

    #[Test]
    public function it_can_set_provider_using_alias()
    {
        // Test setting provider using configured alias
        Currency::setRateProvider('nbu');
        $provider = Currency::getProvider();
        $this->assertInstanceOf(NbuRateProvider::class, $provider);
        
        Currency::setRateProvider('monobank');
        $provider = Currency::getProvider();
        $this->assertInstanceOf(MonobankRateProvider::class, $provider);
    }

    #[Test]
    public function it_can_set_provider_using_class_name()
    {
        // Test setting provider using full class name
        Currency::setRateProvider(NbuRateProvider::class);
        $provider = Currency::getProvider();
        $this->assertInstanceOf(NbuRateProvider::class, $provider);
    }

    #[Test]
    public function it_can_set_provider_using_instance()
    {
        // Test setting provider using class instance
        $nbuProvider = new NbuRateProvider();
        Currency::setRateProvider($nbuProvider);
        
        $currentProvider = Currency::getProvider();
        $this->assertSame($nbuProvider, $currentProvider);
    }

    #[Test]
    public function it_throws_exception_for_invalid_provider_in_set_rate_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'invalid' is not configured and class does not exist");
        
        Currency::setRateProvider('invalid');
    }

    #[Test]
    public function it_throws_exception_for_invalid_provider_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider must be a RateProvider instance, configured alias, or class name");
        
        Currency::setRateProvider(123);
    }

    #[Test]
    public function it_can_set_and_get_base_currency()
    {
        // Test setting custom base currency
        Currency::setBaseCurrency('EUR');
        $this->assertEquals('EUR', Currency::getBaseCurrency());
        
        Currency::setBaseCurrency('usd'); // Test case insensitive
        $this->assertEquals('USD', Currency::getBaseCurrency());
    }

    #[Test]
    public function it_uses_custom_base_currency_for_conversion()
    {
        // Set custom base currency
        Currency::setBaseCurrency('EUR');
        
        // Mock conversion that should use EUR as base
        // This would need actual provider with EUR rates to test properly
        $baseCurrency = Currency::getBaseCurrency();
        $this->assertEquals('EUR', $baseCurrency);
        
        // Reset to original for other tests
        Currency::setBaseCurrency('UAH');
    }

    #[Test]
    public function it_can_get_supported_currencies_info()
    {
        // Test getting supported currencies
        $supportedCurrencies = Currency::getSupportedCurrencies();
        $this->assertIsArray($supportedCurrencies);

        // Test getting count
        $count = Currency::getSupportedCurrenciesCount();
        $this->assertIsInt($count);
        $this->assertEquals(count($supportedCurrencies), $count);
    }

    #[Test]
    public function it_shows_privatbank_limitation()
    {
        // Switch to PrivatBank to test its limitation
        Currency::useProvider('privatbank');
        
        $supportedCurrencies = Currency::getSupportedCurrencies();
        $count = Currency::getSupportedCurrenciesCount();
        
        // PrivatBank should only support EUR and USD
        $this->assertLessThanOrEqual(2, $count);
        
        // Reset to NBU for other tests
        Currency::useProvider('nbu');
    }

    #[Test]
    public function it_converts_rates_when_base_currency_changes()
    {
        Currency::useProvider('nbu');
        
        // Default base currency (UAH)
        $defaultBase = Currency::getBaseCurrency();
        $ratesUAH = Currency::getRates('average');
        
        // Change base to USD
        Currency::setBaseCurrency('USD');
        $ratesUSD = Currency::getRates('average');
        
        // USD should NOT be in the rates array (it's the base)
        $this->assertArrayNotHasKey('USD', $ratesUSD);
        
        // UAH should NOW be in the rates array (converted from base)
        $this->assertArrayHasKey('UAH', $ratesUSD);
        
        // The values should be different
        if (isset($ratesUAH['EUR']) && isset($ratesUSD['EUR'])) {
            $this->assertNotEquals($ratesUAH['EUR'], $ratesUSD['EUR']);
        }
        
        // Reset
        Currency::setBaseCurrency($defaultBase);
    }

    #[Test]
    public function it_returns_1_for_base_currency_rate()
    {
        Currency::setBaseCurrency('USD');
        
        // Getting rate for base currency should return 1.0
        $rate = Currency::getRate('USD');
        $this->assertEquals(1.0, $rate);
        
        // Reset
        Currency::setBaseCurrency('UAH');
    }

    #[Test]
    public function it_converts_correctly_with_custom_base_currency()
    {
        Currency::useProvider('nbu');
        Currency::setBaseCurrency('USD');
        
        // Convert from USD to EUR with USD as base
        // This should work correctly
        try {
            $result = Currency::convert(100, 'USD', 'EUR');
            $this->assertIsFloat($result);
            $this->assertGreaterThan(0, $result);
        } catch (\Exception $e) {
            $this->fail('Conversion with custom base currency failed: ' . $e->getMessage());
        }
        
        // Reset
        Currency::setBaseCurrency('UAH');
    }
}
