<?php

namespace Fomvasss\Currency\Tests\RateProviders;

use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Fomvasss\Currency\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class AbstractRateProviderTest extends TestCase
{
    protected function successResponse(): array
    {
        return [
            [
                'currencyCodeA' => 840, // USD
                'currencyCodeB' => 980, // UAH
                'rateBuy' => 27.5,
                'rateSell' => 28.0,
            ],
        ];
    }

    public function test_successful_fetch_is_cached()
    {
        Http::fake([
            'api.monobank.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new MonobankRateProvider();
        $provider->getRates();
        $provider->getRates();

        Http::assertSentCount(1);
    }

    public function test_error_status_falls_back_to_cache_and_dispatches_event()
    {
        Event::fake([CurrencyRateFetchFailed::class]);

        Cache::put('currency_rates_MonobankRateProvider_fallback', [
            'USD' => ['buy' => 26.0, 'sell' => 26.5],
        ], 86400);

        Http::fake([
            'api.monobank.ua/*' => Http::response([], 500),
        ]);

        $provider = new MonobankRateProvider();
        $rates = $provider->getRates();

        $this->assertEquals(['buy' => 26.0, 'sell' => 26.5], $rates['USD']);

        Event::assertDispatched(CurrencyRateFetchFailed::class, function (CurrencyRateFetchFailed $event) {
            return $event->usingFallback === true;
        });
    }

    public function test_empty_result_is_cached_briefly_and_retried_after_expiry()
    {
        config(['currency.cache_ttl_empty' => 60]);

        Http::fake([
            'api.monobank.ua/*' => Http::sequence()
                ->push([], 500)
                ->push($this->successResponse(), 200),
        ]);

        $provider = new MonobankRateProvider();

        $this->assertEquals([], $provider->getRates());
        $this->assertEquals([], $provider->getRates());
        Http::assertSentCount(1);

        $this->travel(61)->seconds();

        $this->assertNotEmpty($provider->getRates());
        Http::assertSentCount(2);
    }

    public function test_clear_cache_removes_both_keys()
    {
        Cache::put('currency_rates_MonobankRateProvider', ['USD' => ['buy' => 1, 'sell' => 1]], 3600);
        Cache::put('currency_rates_MonobankRateProvider_fallback', ['USD' => ['buy' => 1, 'sell' => 1]], 86400);

        (new MonobankRateProvider())->clearCache();

        $this->assertNull(Cache::get('currency_rates_MonobankRateProvider'));
        $this->assertNull(Cache::get('currency_rates_MonobankRateProvider_fallback'));
    }

    public function test_cache_ttl_defaults_from_config()
    {
        config(['currency.cache_ttl' => 123]);

        Http::fake([
            'api.monobank.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new MonobankRateProvider();
        $provider->getRates();

        // TTL of 123s has not expired, so a second call must not hit the API again.
        $provider->getRates();
        Http::assertSentCount(1);

        Cache::forget('currency_rates_MonobankRateProvider');
        $provider->getRates();
        Http::assertSentCount(2);
    }

    public function test_no_memoization_in_instance()
    {
        Http::fake([
            'api.monobank.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new MonobankRateProvider();
        $provider->getRates();

        Cache::forget('currency_rates_MonobankRateProvider');

        $provider->getRates();

        Http::assertSentCount(2);
    }
}
