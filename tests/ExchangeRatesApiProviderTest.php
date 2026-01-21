<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider;

class ExchangeRatesApiProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url_without_api_key()
    {
        $provider = new ExchangeRatesApiProvider(null, 'UAH');
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://api.frankfurter.app/latest?from=UAH', $url);
    }

    public function test_parse_response_returns_normalized_rates()
    {
        $provider = new ExchangeRatesApiProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'base' => 'UAH',
            'date' => '2024-01-01',
            'rates' => [
                'USD' => 0.0270,
                'EUR' => 0.0250,
                'GBP' => 0.0215,
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(0.0270, $rates['USD']['buy']);
        $this->assertEquals(0.0270, $rates['USD']['sell']);
    }

    public function test_supports_custom_base_currency()
    {
        $provider = new ExchangeRatesApiProvider(null, 'EUR');
        $this->assertEquals('EUR', $provider->getBaseCurrency());
    }
}
