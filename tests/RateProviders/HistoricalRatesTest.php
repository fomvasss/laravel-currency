<?php

namespace Fomvasss\Currency\Tests\RateProviders;

use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Fomvasss\Currency\RateProviders\NbuRateProvider;
use Fomvasss\Currency\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class HistoricalRatesTest extends TestCase
{
    protected function successResponse(): array
    {
        return [
            ['cc' => 'USD', 'rate' => 37.8386],
            ['cc' => 'EUR', 'rate' => 41.0],
        ];
    }

    public function test_get_rates_at_caches_per_date_and_reuses_within_same_date()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();

        $provider->getRatesAt(Carbon::parse('2024-01-15'));
        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        Http::assertSentCount(1);
    }

    public function test_get_rates_at_hits_api_again_for_a_different_date()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();

        $provider->getRatesAt(Carbon::parse('2024-01-15'));
        $provider->getRatesAt(Carbon::parse('2024-01-16'));

        Http::assertSentCount(2);
    }

    public function test_historical_rates_are_cached_forever_by_default()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();
        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        $this->travel(365)->days();

        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        Http::assertSentCount(1);
    }

    public function test_cache_ttl_historical_from_config_expires_the_cache()
    {
        config(['currency.cache_ttl_historical' => 60]);

        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();
        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        $this->travel(61)->seconds();

        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        Http::assertSentCount(2);
    }

    public function test_error_status_returns_empty_array_and_dispatches_event_without_fallback()
    {
        Event::fake([CurrencyRateFetchFailed::class]);

        Cache::put('currency_rates_NbuRateProvider_fallback', [
            'USD' => ['buy' => 26.0, 'sell' => 26.5],
        ], 86400);

        Http::fake([
            'bank.gov.ua/*' => Http::response([], 500),
        ]);

        $provider = new NbuRateProvider();
        $date = Carbon::parse('2024-01-15');
        $rates = $provider->getRatesAt($date);

        $this->assertSame([], $rates);

        Event::assertDispatched(CurrencyRateFetchFailed::class, function (CurrencyRateFetchFailed $event) use ($date) {
            return $event->date?->format('Y-m-d') === $date->format('Y-m-d');
        });
    }

    public function test_not_found_status_returns_empty_array_without_exception()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response([], 404),
        ]);

        $provider = new NbuRateProvider();

        $this->assertSame([], $provider->getRatesAt(Carbon::parse('2020-01-01')));
    }

    public function test_non_array_response_returns_empty_array_and_dispatches_event()
    {
        Event::fake([CurrencyRateFetchFailed::class]);

        Http::fake([
            'bank.gov.ua/*' => Http::response('not-json-array', 200),
        ]);

        $provider = new NbuRateProvider();

        $this->assertSame([], $provider->getRatesAt(Carbon::parse('2024-01-15')));

        Event::assertDispatched(CurrencyRateFetchFailed::class);
    }

    public function test_empty_result_is_cached_briefly_and_retried_after_expiry()
    {
        config(['currency.cache_ttl_empty' => 60]);

        Http::fake([
            'bank.gov.ua/*' => Http::sequence()
                ->push([], 500)
                ->push($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();
        $date = Carbon::parse('2024-01-15');

        $this->assertSame([], $provider->getRatesAt($date));
        $this->assertSame([], $provider->getRatesAt($date));
        Http::assertSentCount(1);

        $this->travel(61)->seconds();

        $this->assertNotEmpty($provider->getRatesAt($date));
        Http::assertSentCount(2);
    }

    public function test_get_rates_at_does_not_touch_regular_rates_cache_and_vice_versa()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();

        $provider->getRates();
        $provider->getRatesAt(Carbon::parse('2024-01-15'));

        Http::assertSentCount(2);

        $this->assertNotNull(Cache::get('currency_rates_NbuRateProvider'));
        $this->assertNotNull(Cache::get('currency_rates_NbuRateProvider_2024-01-15'));
    }

    public function test_get_rate_at_returns_single_currency()
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response($this->successResponse(), 200),
        ]);

        $provider = new NbuRateProvider();
        $rate = $provider->getRateAt('usd', Carbon::parse('2024-01-15'));

        $this->assertEquals(37.8386, $rate['buy']);
    }
}
