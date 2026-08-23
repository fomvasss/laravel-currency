<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;

/**
 * CurrencyAPI provider (https://currencyapi.com/)
 * Requires API key, has free tier with 300 requests/month.
 */
class CurrencyApiProvider extends AbstractRateProvider implements HistoricalRateProvider
{
    protected ?string $apiKey = null;

    public function __construct(?string $apiKey = null, string $baseCurrency = 'UAH')
    {
        $this->apiKey = $apiKey ?? config('currency.currencyapi_key');
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return "https://api.currencyapi.com/v3/latest?apikey={$this->apiKey}&base_currency={$this->baseCurrency}";
    }

    /**
     * Get the API endpoint URL for historical rates as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        return "https://api.currencyapi.com/v3/historical?apikey={$this->apiKey}&base_currency={$this->baseCurrency}&date={$date->format('Y-m-d')}";
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

        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $currency => $data) {
                if (isset($data['value'])) {
                    $rate = (float) $data['value'];

                    // CurrencyAPI provides only mid-market rate
                    $rates[strtoupper($currency)] = [
                        'buy' => $rate,
                        'sell' => $rate,
                    ];
                }
            }
        }

        return $rates;
    }
}
