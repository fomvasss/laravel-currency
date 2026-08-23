<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\PrivatbankRateProvider;

class PrivatbankRateProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url()
    {
        $provider = new PrivatbankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5', $url);
    }

    public function test_parse_response_returns_normalized_rates()
    {
        $provider = new PrivatbankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            [
                'ccy' => 'USD',
                'base_ccy' => 'UAH',
                'buy' => '27.50000',
                'sale' => '28.00000',
            ],
            [
                'ccy' => 'EUR',
                'base_ccy' => 'UAH',
                'buy' => '31.00000',
                'sale' => '32.00000',
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(27.5, $rates['USD']['buy']);
        $this->assertEquals(28.0, $rates['USD']['sell']);
    }

    public function test_base_currency_is_uah()
    {
        $provider = new PrivatbankRateProvider();
        $this->assertEquals('UAH', $provider->getBaseCurrency());
    }

    public function test_parse_response_skips_zero_rates()
    {
        $provider = new PrivatbankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            [
                'ccy' => 'USD',
                'base_ccy' => 'UAH',
                'buy' => '0',
                'sale' => '0',
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertArrayNotHasKey('USD', $rates);
    }

    public function test_get_historical_api_url_returns_correct_url()
    {
        $provider = new PrivatbankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getHistoricalApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider, \Illuminate\Support\Carbon::parse('2024-01-15'));
        $this->assertEquals('https://api.privatbank.ua/p24api/exchange_rates?json&date=15.01.2024', $url);
    }

    public function test_parse_historical_response_uses_purchase_and_sale_rate()
    {
        $provider = new PrivatbankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseHistoricalResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'exchangeRate' => [
                [
                    'currency' => 'USD',
                    'purchaseRate' => 37.5,
                    'saleRate' => 38.0,
                    'purchaseRateNB' => 37.8,
                    'saleRateNB' => 37.8,
                ],
                [
                    'currency' => 'UAH',
                    'baseCurrency' => 'UAH',
                ],
                [
                    'currency' => 'PLN',
                    'purchaseRateNB' => 9.5,
                    'saleRateNB' => 9.5,
                ],
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertEquals(37.5, $rates['USD']['buy']);
        $this->assertEquals(38.0, $rates['USD']['sell']);
        $this->assertArrayNotHasKey('UAH', $rates);
        // PLN has no own purchaseRate/saleRate — the NBU-only fields must not be used as a substitute.
        $this->assertArrayNotHasKey('PLN', $rates);
    }
}
