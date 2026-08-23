<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\NbuRateProvider;

class NbuRateProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url()
    {
        $provider = new NbuRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json', $url);
    }

    public function test_parse_response_returns_normalized_rates_without_base_currency()
    {
        $provider = new NbuRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            ['cc' => 'USD', 'rate' => 27.5],
            ['cc' => 'EUR', 'rate' => 31.0],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertArrayHasKey('USD', $rates);
        $this->assertEquals(27.5, $rates['USD']['buy']);
        $this->assertEquals(27.5, $rates['USD']['sell']);
        $this->assertArrayNotHasKey('UAH', $rates);
    }

    public function test_parse_response_skips_items_missing_cc_or_rate()
    {
        $provider = new NbuRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            ['cc' => 'USD'],
            ['rate' => 27.5],
            ['cc' => 'EUR', 'rate' => 31.0],
        ];

        $rates = $method->invoke($provider, $apiResponse);

        $this->assertArrayNotHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
    }
}
