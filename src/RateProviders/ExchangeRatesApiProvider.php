<?php

namespace Fomvasss\Currency\RateProviders;

/**
 * Exchange Rates API provider (https://exchangeratesapi.io/)
 * Free tier available, supports multiple base currencies.
 */
class ExchangeRatesApiProvider extends AbstractRateProvider
{
    protected ?string $apiKey = null;

    public function __construct(?string $apiKey = null, string $baseCurrency = 'UAH')
    {
        $this->apiKey = $apiKey ?? config('currency.exchange_rates_api_key');
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        if ($this->apiKey) {
            // Paid version with API key
            return "https://api.exchangeratesapi.io/v1/latest?access_key={$this->apiKey}&base={$this->baseCurrency}";
        }

        // Using frankfurter.app as free alternative (no API key required)
        return "https://api.frankfurter.app/latest?from={$this->baseCurrency}";
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

        if (isset($response['rates']) && is_array($response['rates'])) {
            foreach ($response['rates'] as $currency => $rate) {
                // For international API, we typically only have mid-market rate
                // So we use the same rate for buy and sell
                $rates[strtoupper($currency)] = [
                    'buy' => (float) $rate,
                    'sell' => (float) $rate,
                ];
            }
        }

        return $rates;
    }
}
