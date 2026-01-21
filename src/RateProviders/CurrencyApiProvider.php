<?php

namespace Fomvasss\Currency\RateProviders;

/**
 * CurrencyAPI provider (https://currencyapi.com/)
 * Requires API key, has free tier with 300 requests/month.
 */
class CurrencyApiProvider extends AbstractRateProvider
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

    /**
     * Get fallback rates when API fails.
     *
     * @return array
     */
    protected function getFallbackRates(): array
    {
        // Try without API key using free endpoint (very limited)
        if ($this->apiKey) {
            return [];
        }

        return [];
    }
}
