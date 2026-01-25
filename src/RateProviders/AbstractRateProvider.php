<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\RateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class AbstractRateProvider implements RateProvider
{
    protected array $rates = [];
    protected string $baseCurrency = 'UAH';
    protected int $cacheTtl = 3600; // 1 hour

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
        if (empty($this->rates)) {
            $this->rates = $this->fetchRates();
        }

        return $this->rates;
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
        $fallbackCacheKey = $this->getCacheKey() . '_fallback';
        $fallbackTtl = config('currency.cache_ttl_fallback', 86400); // 1 day

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($fallbackCacheKey, $fallbackTtl) {
            try {
                $response = Http::timeout(10)->get($this->getApiUrl());

                if ($response->successful()) {
                    $rates = $this->parseResponse($response->json());
                    
                    // Store successful rates in long-term fallback cache
                    if (!empty($rates)) {
                        Cache::put($fallbackCacheKey, $rates, $fallbackTtl);
                    }
                    
                    return $rates;
                }

                // Try fallback cache if API returns error
                return $this->tryFallbackCache($fallbackCacheKey);
            } catch (\Exception $e) {
                \Log::error('Currency rate provider error: ' . $e->getMessage());
                
                // Try fallback cache on exception
                return $this->tryFallbackCache($fallbackCacheKey);
            }
        });
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
            \Log::warning('Using fallback cached rates for ' . class_basename($this));
            return $fallbackRates;
        }
        
        // Last resort - static fallback rates
        \Log::error('No cached rates available, using static fallback for ' . class_basename($this));
        return $this->getFallbackRates();
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
}
