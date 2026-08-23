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
