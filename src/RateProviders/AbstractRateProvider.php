<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\RateProvider;
use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractRateProvider implements RateProvider
{
    protected string $baseCurrency = 'UAH';
    protected ?int $cacheTtl = null;

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    abstract protected function getApiUrl(): string;

    /**
     * Parse API response and return normalized rates.
     *
     * @param mixed $response
     * @return array
     */
    abstract protected function parseResponse($response): array;

    /**
     * Get the API endpoint URL for historical rates as of a specific date.
     * Providers that support historical rates (and implement HistoricalRateProvider) override this.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        throw new \LogicException(class_basename($this) . ' does not support historical rates');
    }

    /**
     * Parse historical API response and return normalized rates.
     * Defaults to parseResponse() for providers whose historical response has the same shape.
     *
     * @param mixed $response
     * @return array
     */
    protected function parseHistoricalResponse($response): array
    {
        return $this->parseResponse($response);
    }

    /**
     * Get exchange rates for all supported currencies.
     *
     * @return array
     */
    public function getRates(): array
    {
        return $this->fetchRates();
    }

    /**
     * Get exchange rate for specific currency.
     *
     * @param string $currency
     * @return array|null
     */
    public function getRate(string $currency): ?array
    {
        $rates = $this->getRates();

        return $rates[strtoupper($currency)] ?? null;
    }

    /**
     * Get exchange rates for all supported currencies as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return array
     */
    public function getRatesAt(\DateTimeInterface $date): array
    {
        return $this->fetchRatesAt($date);
    }

    /**
     * Get exchange rate for specific currency as of a specific date.
     *
     * @param string $currency
     * @param \DateTimeInterface $date
     * @return array|null
     */
    public function getRateAt(string $currency, \DateTimeInterface $date): ?array
    {
        $rates = $this->getRatesAt($date);

        return $rates[strtoupper($currency)] ?? null;
    }

    /**
     * Check if the provider supports given currency.
     *
     * @param string $currency
     * @return bool
     */
    public function supports(string $currency): bool
    {
        return isset($this->getRates()[strtoupper($currency)]);
    }

    /**
     * Get the base currency code for this provider.
     *
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * Get list of supported currency codes.
     *
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return array_keys($this->getRates());
    }

    /**
     * Get count of supported currencies.
     *
     * @return int
     */
    public function getSupportedCurrenciesCount(): int
    {
        return count($this->getRates());
    }

    /**
     * Fetch rates from API with caching.
     *
     * @return array
     */
    protected function fetchRates(): array
    {
        $cacheKey = $this->getCacheKey();

        $rates = Cache::get($cacheKey);

        if ($rates !== null) {
            return $rates;
        }

        $rates = $this->fetchRatesFromApi();

        // An empty result (API down, no fallback) is cached only briefly so the next
        // request retries soon, but a burst of calls doesn't hammer the API.
        $ttl = empty($rates) ? config('currency.cache_ttl_empty', 60) : $this->getCacheTtl();

        Cache::put($cacheKey, $rates, $ttl);

        return $rates;
    }

    /**
     * Fetch rates from the remote API, falling back to cached/static rates on failure.
     *
     * @return array
     */
    protected function fetchRatesFromApi(): array
    {
        $fallbackCacheKey = $this->getCacheKey() . '_fallback';
        $fallbackTtl = config('currency.cache_ttl_fallback', 86400); // 1 day

        try {
            $response = Http::timeout(10)->get($this->getApiUrl());

            if ($response->successful()) {
                $json = $response->json();

                if (!is_array($json)) {
                    event(new CurrencyRateFetchFailed(
                        static::class,
                        'API returned a non-array response',
                        false
                    ));

                    return $this->tryFallbackCache($fallbackCacheKey);
                }

                $rates = $this->parseResponse($json);

                // Store successful rates in long-term fallback cache
                if (!empty($rates)) {
                    Cache::put($fallbackCacheKey, $rates, $fallbackTtl);
                }

                return $rates;
            }

            // API returned error status
            event(new CurrencyRateFetchFailed(
                static::class,
                'API returned error status: ' . $response->status(),
                false
            ));

            // Try fallback cache if API returns error
            return $this->tryFallbackCache($fallbackCacheKey);
        } catch (\Throwable $e) {
            Log::error('Currency rate provider error: ' . $e->getMessage());

            // Dispatch event for exception
            event(new CurrencyRateFetchFailed(
                static::class,
                $e->getMessage(),
                false
            ));

            // Try fallback cache on exception
            return $this->tryFallbackCache($fallbackCacheKey);
        }
    }

    /**
     * Try to get rates from fallback cache or use static rates.
     *
     * @param string $fallbackCacheKey
     * @return array
     */
    protected function tryFallbackCache(string $fallbackCacheKey): array
    {
        // Try to get from long-term cache
        $fallbackRates = Cache::get($fallbackCacheKey);
        
        if ($fallbackRates && !empty($fallbackRates)) {
            Log::warning('Using fallback cached rates for ' . class_basename($this));
            
            // Dispatch event that we're using fallback cache
            event(new CurrencyRateFetchFailed(
                static::class,
                'Using fallback cached rates',
                true,
                $fallbackRates
            ));
            
            return $fallbackRates;
        }
        
        // Last resort - static fallback rates
        Log::error('No cached rates available, using static fallback for ' . class_basename($this));
        
        $staticRates = $this->getFallbackRates();
        
        // Dispatch event that we're using static fallback
        event(new CurrencyRateFetchFailed(
            static::class,
            'No cached rates available, using static fallback',
            true,
            $staticRates
        ));
        
        return $staticRates;
    }

    /**
     * Fetch historical rates from API with caching.
     *
     * @param \DateTimeInterface $date
     * @return array
     */
    protected function fetchRatesAt(\DateTimeInterface $date): array
    {
        $cacheKey = $this->getHistoricalCacheKey($date);

        $rates = Cache::get($cacheKey);

        if ($rates !== null) {
            return $rates;
        }

        $rates = $this->fetchRatesFromApiAt($date);

        if (empty($rates)) {
            Cache::put($cacheKey, $rates, config('currency.cache_ttl_empty', 60));

            return $rates;
        }

        $ttl = config('currency.cache_ttl_historical');

        if ($ttl === null) {
            Cache::forever($cacheKey, $rates);
        } else {
            Cache::put($cacheKey, $rates, $ttl);
        }

        return $rates;
    }

    /**
     * Fetch historical rates from the remote API for a specific date.
     * Unlike fetchRatesFromApi(), there is no fallback cache: a rate for another day
     * is worse than no rate at all.
     *
     * @param \DateTimeInterface $date
     * @return array
     */
    protected function fetchRatesFromApiAt(\DateTimeInterface $date): array
    {
        $url = $this->getHistoricalApiUrl($date);

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $json = $response->json();

                if (!is_array($json)) {
                    Log::warning('Currency historical rate provider error: API returned a non-array response for ' . class_basename($this));

                    event(new CurrencyRateFetchFailed(
                        static::class,
                        'API returned a non-array response',
                        false,
                        null,
                        $date
                    ));

                    return [];
                }

                return $this->parseHistoricalResponse($json);
            }

            Log::warning('Currency historical rate provider error: API returned error status ' . $response->status() . ' for ' . class_basename($this));

            event(new CurrencyRateFetchFailed(
                static::class,
                'API returned error status: ' . $response->status(),
                false,
                null,
                $date
            ));

            return [];
        } catch (\Throwable $e) {
            Log::warning('Currency historical rate provider error: ' . $e->getMessage());

            event(new CurrencyRateFetchFailed(
                static::class,
                $e->getMessage(),
                false,
                null,
                $date
            ));

            return [];
        }
    }

    /**
     * Get cache key for historical rates on a specific date.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalCacheKey(\DateTimeInterface $date): string
    {
        return $this->getCacheKey() . '_' . $date->format('Y-m-d');
    }

    /**
     * Get cache key for this provider.
     *
     * @return string
     */
    protected function getCacheKey(): string
    {
        return 'currency_rates_' . class_basename($this);
    }

    /**
     * Get fallback rates when API fails.
     *
     * @return array
     */
    protected function getFallbackRates(): array
    {
        return [];
    }

    /**
     * Get cache TTL in seconds.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return $this->cacheTtl ?? config('currency.cache_ttl', 3600);
    }

    /**
     * Set cache TTL in seconds.
     *
     * @param int $seconds
     * @return $this
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;
        return $this;
    }

    /**
     * Clear both regular and fallback cache for this provider.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
        Cache::forget($this->getCacheKey() . '_fallback');
    }
}
