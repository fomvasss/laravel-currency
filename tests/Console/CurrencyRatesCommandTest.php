<?php

namespace Fomvasss\Currency\Tests\Console;

use Fomvasss\Currency\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyRatesCommandTest extends TestCase
{
    public function test_show_currency_option_displays_rate()
    {
        Http::fake([
            'api.monobank.ua/*' => Http::response([
                [
                    'currencyCodeA' => 840, // USD
                    'currencyCodeB' => 980, // UAH
                    'rateBuy' => 27.5,
                    'rateSell' => 28.0,
                ],
            ], 200),
        ]);

        $this->artisan('currency:rates', ['--currency' => 'USD'])
            ->expectsOutputToContain('27.5')
            ->assertExitCode(0);
    }

    public function test_refresh_clears_cache_for_selected_provider()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response([
                ['cc' => 'USD', 'rate' => 27.5],
            ], 200),
        ]);

        Cache::put('currency_rates_NbuRateProvider', ['USD' => ['buy' => 1, 'sell' => 1]], 3600);

        $this->artisan('currency:rates', ['--provider' => 'nbu', '--refresh' => true])
            ->assertExitCode(0);

        Http::assertSentCount(1);
    }

    public function test_date_option_shows_historical_rate()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response([
                ['cc' => 'USD', 'rate' => 37.8386],
            ], 200),
        ]);

        $this->artisan('currency:rates', ['--provider' => 'nbu', '--currency' => 'USD', '--date' => '2024-01-15'])
            ->expectsOutputToContain('Date: 2024-01-15')
            ->expectsOutputToContain('37.8386')
            ->assertExitCode(0);
    }

    public function test_date_option_fails_for_provider_without_historical_support()
    {
        $this->artisan('currency:rates', ['--provider' => 'monobank', '--date' => '2024-01-15'])
            ->assertExitCode(1);
    }
}
