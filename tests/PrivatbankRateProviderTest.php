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
}
