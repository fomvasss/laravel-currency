<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\Contracts\RateProvider;
use Fomvasss\Currency\Currency;

class MockRateProvider implements RateProvider
{
    protected array $rates = [
        'USD' => ['buy' => 40.00, 'sell' => 41.00],
        'EUR' => ['buy' => 43.00, 'sell' => 44.00],
        'GBP' => ['buy' => 50.00, 'sell' => 51.00],
    ];

    public function getRates(): array
    {
        return $this->rates;
    }

    public function getRate(string $currency): ?array
    {
        return $this->rates[strtoupper($currency)] ?? null;
    }

    public function supports(string $currency): bool
    {
        return isset($this->rates[strtoupper($currency)]);
    }

    public function getBaseCurrency(): string
    {
        return 'UAH';
    }

    public function setRates(array $rates): void
    {
        $this->rates = $rates;
    }
}

class CurrencyTest extends TestCase
{
    protected Currency $currency;
    protected MockRateProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new MockRateProvider();
        $this->currency = new Currency($this->provider, config('currency'));
    }

    public function test_same_currency_conversion_returns_same_amount()
    {
        $result = $this->currency->convert(100, 'USD', 'USD');
        $this->assertEquals(100, $result);
    }

    public function test_convert_usd_to_eur_with_average_rate()
    {
        // 100 USD * 40.5 (avg UAH rate) = 4050 UAH
        // 4050 UAH / 43.5 (avg EUR rate) = 93.10 EUR
        $result = $this->currency->convert(100, 'USD', 'EUR', 'average');
        $this->assertEquals(93.10, $result);
    }

    public function test_convert_with_buy_rate()
    {
        // 100 USD * 40 (buy rate) = 4000 UAH
        // 4000 UAH / 43 (buy rate) = 93.02 EUR
        $result = $this->currency->convert(100, 'USD', 'EUR', 'buy');
        $this->assertEquals(93.02, $result);
    }

    public function test_convert_with_sell_rate()
    {
        // 100 USD * 41 (sell rate) = 4100 UAH
        // 4100 UAH / 44 (sell rate) = 93.18 EUR
        $result = $this->currency->convert(100, 'USD', 'EUR', 'sell');
        $this->assertEquals(93.18, $result);
    }

    public function test_convert_uses_config_default_rate_type()
    {
        // Set config default to 'buy'
        $config = config('currency');
        $config['default_rate_type'] = 'buy';
        $currency = new Currency($this->provider, $config);

        // Convert without specifying rate type - should use 'buy'
        $result = $currency->convert(100, 'USD', 'EUR');

        // 100 USD * 40 (buy rate) = 4000 UAH
        // 4000 UAH / 43 (buy rate) = 93.02 EUR
        $this->assertEquals(93.02, $result);
    }

    public function test_convert_explicit_rate_type_overrides_config()
    {
        // Set config default to 'buy'
        $config = config('currency');
        $config['default_rate_type'] = 'buy';
        $currency = new Currency($this->provider, $config);

        // Explicitly use 'sell' - should override config
        $result = $currency->convert(100, 'USD', 'EUR', 'sell');

        // 100 USD * 41 (sell rate) = 4100 UAH
        // 4100 UAH / 44 (sell rate) = 93.18 EUR
        $this->assertEquals(93.18, $result);
    }

    public function test_convert_from_base_currency()
    {
        // 4000 UAH / 40.5 (avg USD rate) = 98.77 USD
        $result = $this->currency->convert(4000, 'UAH', 'USD');
        $this->assertEquals(98.77, $result);
    }

    public function test_convert_to_base_currency()
    {
        // 100 USD * 40.5 (avg rate) = 4050 UAH
        $result = $this->currency->convert(100, 'USD', 'UAH');
        $this->assertEquals(4050.00, $result);
    }

    public function test_get_rate_returns_correct_value()
    {
        $rate = $this->currency->getRate('USD', 'buy');
        $this->assertEquals(40.00, $rate);

        $rate = $this->currency->getRate('USD', 'sell');
        $this->assertEquals(41.00, $rate);

        $rate = $this->currency->getRate('USD', 'average');
        $this->assertEquals(40.50, $rate);
    }

    public function test_get_all_rates()
    {
        $rates = $this->currency->getRates('all');

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('buy', $rates['USD']);
        $this->assertArrayHasKey('sell', $rates['USD']);
    }

    public function test_get_active_currencies()
    {
        $currencies = $this->currency->getActiveCurrencies();

        $this->assertIsArray($currencies);
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('EUR', $currencies);
        $this->assertArrayNotHasKey('GBP', $currencies); // GBP is not active
    }

    public function test_get_active_currency_codes()
    {
        $codes = $this->currency->getActiveCurrencyCodes();

        $this->assertIsArray($codes);
        $this->assertContains('USD', $codes);
        $this->assertContains('EUR', $codes);
        $this->assertNotContains('GBP', $codes);
    }

    public function test_format_currency_with_symbol()
    {
        $formatted = $this->currency->format(1234.56, 'USD');
        $this->assertEquals('$ 1,234.56', $formatted);

        $formatted = $this->currency->format(1234.56, 'UAH');
        $this->assertEquals('1 234.56 ₴', $formatted);
    }

    public function test_format_currency_without_symbol()
    {
        $formatted = $this->currency->format(1234.56, 'USD', false);
        $this->assertEquals('1,234.56', $formatted);
    }

    public function test_format_currency_with_euro_style()
    {
        $formatted = $this->currency->format(1234.56, 'EUR');
        $this->assertEquals('€ 1.234,56', $formatted);
    }

    public function test_is_supported_returns_true_for_supported_currency()
    {
        $this->assertTrue($this->currency->isSupported('USD'));
        $this->assertTrue($this->currency->isSupported('eur')); // case insensitive
    }

    public function test_is_supported_returns_false_for_unsupported_currency()
    {
        $this->assertFalse($this->currency->isSupported('XYZ'));
    }

    public function test_get_base_currency()
    {
        $base = $this->currency->getBaseCurrency();
        $this->assertEquals('UAH', $base);
    }

    public function test_get_currency_config()
    {
        $config = $this->currency->getCurrencyConfig('USD');

        $this->assertIsArray($config);
        $this->assertEquals('USD', $config['code']);
        $this->assertEquals('$', $config['symbol']);
        $this->assertEquals(2, $config['precision']);
    }

    public function test_get_precision()
    {
        $precision = $this->currency->getPrecision('USD');
        $this->assertEquals(2, $precision);
    }

    public function test_set_rate_provider()
    {
        $newProvider = new MockRateProvider();
        $newProvider->setRates([
            'USD' => ['buy' => 50.00, 'sell' => 52.00],
        ]);

        $this->currency->setRateProvider($newProvider);

        $rate = $this->currency->getRate('USD', 'buy');
        $this->assertEquals(50.00, $rate);
    }

    public function test_get_rate_provider()
    {
        $provider = $this->currency->getRateProvider();
        $this->assertInstanceOf(RateProvider::class, $provider);
    }

    public function test_convert_throws_exception_for_unsupported_currency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->currency->convert(100, 'XYZ', 'USD');
    }
}
