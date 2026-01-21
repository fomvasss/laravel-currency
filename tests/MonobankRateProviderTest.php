<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Illuminate\Support\Facades\Http;

class MonobankRateProviderTest extends TestCase
{
    public function test_get_api_url_returns_correct_url()
    {
        $provider = new MonobankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiUrl');
        $method->setAccessible(true);

        $url = $method->invoke($provider);
        $this->assertEquals('https://api.monobank.ua/bank/currency', $url);
    }

    public function test_parse_response_returns_normalized_rates()
    {
        $provider = new MonobankRateProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $apiResponse = [
            [
                'currencyCodeA' => 840, // USD
                'currencyCodeB' => 980, // UAH
                'date' => 1640000000,
                'rateBuy' => 27.5,
                'rateSell' => 28.0,
            ],
            [
                'currencyCodeA' => 978, // EUR
                'currencyCodeB' => 980, // UAH
                'date' => 1640000000,
                'rateBuy' => 31.0,
                'rateSell' => 32.0,
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
        $provider = new MonobankRateProvider();
        $this->assertEquals('UAH', $provider->getBaseCurrency());
    }
}
