<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\FixerProvider;

class FixerProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url()
    {
        $provider = new FixerProvider('test_key', 'UAH');
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://api.fixer.io/latest?access_key=test_key&base=UAH', $url);
    }

    public function test_parse_response_returns_normalized_rates()
    {
        $provider = new FixerProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'success' => true,
            'timestamp' => 1640000000,
            'base' => 'UAH',
            'date' => '2024-01-01',
            'rates' => [
                'USD' => 0.025,
                'EUR' => 0.023,
                'GBP' => 0.020,
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(0.025, $rates['USD']['buy']);
        $this->assertEquals(0.025, $rates['USD']['sell']);
    }

    public function test_parse_response_handles_failed_request()
    {
        $provider = new FixerProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'success' => false,
            'error' => [
                'code' => 101,
                'type' => 'invalid_access_key',
            ],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertIsArray($rates);
        $this->assertEmpty($rates);
    }

    public function test_supports_custom_base_currency()
    {
        $provider = new FixerProvider(null, 'EUR');
        $this->assertEquals('EUR', $provider->getBaseCurrency());
    }
}
