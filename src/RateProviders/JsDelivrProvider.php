<?php

namespace Fomvasss\Currency\RateProviders;

/**
 * jsDelivr CDN Rate Provider
 * 
 * Uses free currency API data delivered via jsDelivr CDN.
 * Data source: @fawazahmed0/currency-api
 * 
 * Note: This is a good fallback provider as it's free and has no rate limits,
 * but data may not be real-time (updated daily).
 * 
 * Supports 150+ currencies.
 */
class JsDelivrProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'EUR';
    protected string $baseUrl = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1';

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        $currency = strtolower($this->baseCurrency);
        return "{$this->baseUrl}/currencies/{$currency}.json";
    }

    /**
     * Parse API response and return normalized rates.
     * 
     * jsDelivr API returns data in format:
     * {
     *   "date": "2024-01-22",
     *   "uah": {
     *     "usd": 0.023,
     *     "eur": 0.021,
     *     ...
     *   }
     * }
     *
     * @param mixed $response
     * @return array
     */
    protected function parseResponse($response): array
    {
        $rates = [];
        
        if (!isset($response[$this->baseCurrency]) && !isset($response[strtolower($this->baseCurrency)])) {
            return $rates;
        }

        $currencyData = $response[strtolower($this->baseCurrency)] ?? $response[$this->baseCurrency];

        foreach ($currencyData as $currencyCode => $rate) {
            $code = strtoupper($currencyCode);
            
            // Skip the base currency itself
            if ($code === $this->baseCurrency) {
                continue;
            }

            // jsDelivr API returns inverse rates (UAH to other currencies)
            // We need to convert to: 1 foreign currency = X base currency
            $inverseRate = 1 / (float) $rate;

            // Since we don't have buy/sell spread, use the same rate with small spread
            $spread = 0.01; // 1% spread
            $rates[$code] = [
                'buy' => $inverseRate * (1 - $spread / 2),
                'sell' => $inverseRate * (1 + $spread / 2),
            ];
        }

        return $rates;
    }

    /**
     * Get fallback rates when API fails.
     * 
     * Provides basic rates for major currencies.
     *
     * @return array
     */
    protected function getFallbackRates(): array
    {
        return [
            'USD' => ['buy' => 41.0, 'sell' => 42.0],
            'EUR' => ['buy' => 45.0, 'sell' => 46.0],
            'GBP' => ['buy' => 52.0, 'sell' => 53.0],
        ];
    }
}
