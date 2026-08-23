<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;

/**
 * Fixer.io provider (https://fixer.io/)
 * Requires API key, has free tier with 100 requests/month.
 */
class FixerProvider extends AbstractRateProvider implements HistoricalRateProvider
{
    protected ?string $apiKey = null;
    protected string $apiUrl = 'https://data.fixer.io/api/latest';

    public function __construct(?string $apiKey = null, string $baseCurrency = 'UAH')
    {
        $this->apiKey = $apiKey ?? config('currency.fixer_api_key');
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return "{$this->apiUrl}?access_key={$this->apiKey}&base={$this->baseCurrency}";
    }

    /**
     * Get the API endpoint URL for historical rates as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        return dirname($this->apiUrl) . "/{$date->format('Y-m-d')}?access_key={$this->apiKey}&base={$this->baseCurrency}";
    }

    /**
     * Parse API response and return normalized rates.
     *
     * @param mixed $response
     * @return array
     */
    protected function parseResponse($response): array
    {
        $rates = [];

        if (isset($response['success']) && $response['success'] === true) {
            if (isset($response['rates']) && is_array($response['rates'])) {
                foreach ($response['rates'] as $currency => $rate) {
                    $rateValue = (float) $rate;

                    // Fixer provides only mid-market rate
                    $rates[strtoupper($currency)] = [
                        'buy' => $rateValue,
                        'sell' => $rateValue,
                    ];
                }
            }
        }

        return $rates;
    }

    /**
     * Get fallback rates when API fails.
     *
     * @return array
     */
    protected function getFallbackRates(): array
    {
        // Fixer requires API key, no free fallback available
        return [];
    }
}
