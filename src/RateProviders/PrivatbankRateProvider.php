<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;

/**
 * PrivatBank Rate Provider
 *
 * Note: PrivatBank API provides exchange rates only for EUR and USD currencies.
 * This is a limitation of their public API, not the implementation.
 * For more currencies, consider using NBU or other providers.
 */
class PrivatbankRateProvider extends AbstractRateProvider implements HistoricalRateProvider
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
                $buy = (float) ($item['buy'] ?? 0);
                $sell = (float) ($item['sale'] ?? 0);

                if ($buy > 0 && $sell > 0) {
                    $rates[$currencyCode] = [
                        'buy' => $buy,
                        'sell' => $sell,
                    ];
                }
            }
        }

        return $rates;
    }

    /**
     * Get the API endpoint URL for historical rates as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        return 'https://api.privatbank.ua/p24api/exchange_rates?json&date=' . $date->format('d.m.Y');
    }

    /**
     * Parse historical API response and return normalized rates.
     *
     * Note: unlike the current-rate endpoint, `purchaseRate`/`saleRate` are present only
     * for USD/EUR (and not always). The `*NB` fields are the NBU rate, not this bank's own
     * rate, so they are never used as a substitute here.
     *
     * @param mixed $response
     * @return array
     */
    protected function parseHistoricalResponse($response): array
    {
        $rates = [];

        foreach ($response['exchangeRate'] ?? [] as $item) {
            if (!isset($item['currency'])) {
                continue;
            }

            $currencyCode = strtoupper($item['currency']);

            if ($currencyCode === $this->baseCurrency) {
                continue;
            }

            $buy = (float) ($item['purchaseRate'] ?? 0);
            $sell = (float) ($item['saleRate'] ?? 0);

            if ($buy > 0 && $sell > 0) {
                $rates[$currencyCode] = [
                    'buy' => $buy,
                    'sell' => $sell,
                ];
            }
        }

        return $rates;
    }
}
