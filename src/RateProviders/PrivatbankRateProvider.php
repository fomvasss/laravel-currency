<?php

namespace Fomvasss\Currency\RateProviders;

/**
 * PrivatBank Rate Provider
 * 
 * Note: PrivatBank API provides exchange rates only for EUR and USD currencies.
 * This is a limitation of their public API, not the implementation.
 * For more currencies, consider using NBU or other providers.
 */
class PrivatbankRateProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';

    /**
     * Get the API endpoint URL.
     * 
     * PrivatBank public API endpoint that returns EUR and USD rates only.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return 'https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5';
                https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5
    }

    /**
     * Parse API response and return normalized rates.
     * 
     * Note: PrivatBank API returns only EUR and USD rates.
     *
     * @param mixed $response
     * @return array
     */
    protected function parseResponse($response): array
    {
        $rates = [];

        foreach ($response as $item) {
            // PrivatBank API returns data in base_ccy => ccy format
            // Currently supports only EUR and USD
            if (isset($item['ccy'], $item['base_ccy']) && $item['base_ccy'] === 'UAH') {
                $currencyCode = strtoupper($item['ccy']);

                $rates[$currencyCode] = [
                    'buy' => (float) ($item['buy'] ?? 0),
                    'sell' => (float) ($item['sale'] ?? 0),
                ];
            }
        }

        return $rates;
    }
}
