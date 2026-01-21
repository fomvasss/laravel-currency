<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\CurrencyApiProvider;

class CurrencyApiProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url()
    {
        $provider = new CurrencyApiProvider('test_key', 'UAH');
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://api.currencyapi.com/v3/latest?apikey=test_key&base_currency=UAH', $url);
    }

    public function test_parse_response_returns_normalized_rates()
    {
        $provider = new CurrencyApiProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'data' => [
                'USD' => [
                    'code' => 'USD',
                    'value' => 0.025,
                ],
                'EUR' => [
                    'code' => 'EUR',
                    'value' => 0.023,
                ],
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(0.025, $rates['USD']['buy']);
        $this->assertEquals(0.025, $rates['USD']['sell']);
    }

    public function test_supports_custom_base_currency()
    {
        $provider = new CurrencyApiProvider(null, 'EUR');
        $this->assertEquals('EUR', $provider->getBaseCurrency());
    }
}
