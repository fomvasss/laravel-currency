<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;

/**
 * Exchange Rates API provider (https://exchangeratesapi.io/)
 * Free tier available, supports multiple base currencies.
 */
class ExchangeRatesApiProvider extends AbstractRateProvider implements HistoricalRateProvider
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

        // Using frankfurter.dev as free alternative (no API key required)
        return "https://api.frankfurter.dev/v1/latest?from={$this->baseCurrency}";
    }

    /**
     * Get the API endpoint URL for historical rates as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        $formattedDate = $date->format('Y-m-d');

        if ($this->apiKey) {
            return "https://api.exchangeratesapi.io/v1/{$formattedDate}?access_key={$this->apiKey}&base={$this->baseCurrency}";
        }

        return "https://api.frankfurter.dev/v1/{$formattedDate}?from={$this->baseCurrency}";
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
                if ((float) $rate <= 0) {
                    continue;
                }

                // The API returns foreign units per 1 base unit; we store base units per 1 foreign unit.
                // Mid-market rate only, so buy and sell are the same
                $rates[strtoupper($currency)] = [
                    'buy' => 1 / (float) $rate,
                    'sell' => 1 / (float) $rate,
                ];
            }
        }

        return $rates;
    }
}
