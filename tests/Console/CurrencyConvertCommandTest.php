<?php

namespace Fomvasss\Currency\Tests\Console;

use Fomvasss\Currency\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class CurrencyConvertCommandTest extends TestCase
{
    public function test_convert_with_date_uses_historical_rate()
    {
        config(['currency.default_provider' => 'nbu']);

        Http::fake([
            'bank.gov.ua/*' => Http::response([
                ['cc' => 'USD', 'rate' => 37.8386],
            ], 200),
        ]);

        $this->artisan('currency:convert', ['amount' => 100, 'from' => 'usd', 'to' => 'uah', '--date' => '2024-01-15'])
            ->expectsOutputToContain('Date: 2024-01-15')
            ->expectsOutputToContain('3783.86')
            ->assertExitCode(0);
    }

    public function test_convert_with_date_fails_for_provider_without_historical_support()
    {
        config(['currency.default_provider' => 'monobank']);

        $this->artisan('currency:convert', ['amount' => 100, 'from' => 'usd', 'to' => 'uah', '--date' => '2024-01-15'])
            ->assertExitCode(1);
    }
}
