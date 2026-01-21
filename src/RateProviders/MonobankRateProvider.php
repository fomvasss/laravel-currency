<?php

namespace Fomvasss\Currency\RateProviders;

class MonobankRateProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';

    /**
     * Mapping of ISO 4217 numeric codes to currency codes.
     */
    protected array $currencyCodeMap = [
        980 => 'UAH',
        840 => 'USD',
        978 => 'EUR',
        985 => 'PLN',
        826 => 'GBP',
        124 => 'CAD',
        203 => 'CZK',
        208 => 'DKK',
        348 => 'HUF',
        392 => 'JPY',
        578 => 'NOK',
        752 => 'SEK',
        756 => 'CHF',
        036 => 'AUD',
        156 => 'CNY',
        949 => 'TRY',
    ];

    /**
     * Get the API endpoint URL.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return 'https://api.monobank.ua/bank/currency';
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

        foreach ($response as $item) {
            // Only process rates against UAH (currency B = 980)
            if (isset($item['currencyCodeB']) && $item['currencyCodeB'] == 980) {
                $currencyCode = $this->currencyCodeMap[$item['currencyCodeA']] ?? null;

                if ($currencyCode) {
                    $rates[$currencyCode] = [
                        'buy' => (float) ($item['rateBuy'] ?? $item['rateCross'] ?? 0),
                        'sell' => (float) ($item['rateSell'] ?? $item['rateCross'] ?? 0),
                    ];
                }
            }
        }

        return $rates;
    }
}
