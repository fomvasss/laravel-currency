<?php

namespace Fomvasss\Currency\RateProviders;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;

class NbuRateProvider extends AbstractRateProvider implements HistoricalRateProvider
{
    protected function getApiUrl(): string
    {
        return 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
    }

    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        return $this->getApiUrl() . '&date=' . $date->format('Ymd');
    }

    protected function parseResponse($response): array
    {
        $rates = [];

        foreach ($response as $item) {
            if (!isset($item['cc'], $item['rate'])) {
                continue;
            }

            $code = $item['cc'];
            $rate = (float) $item['rate'];

            $rates[$code] = [
                'buy' => $rate,
                'sell' => $rate,
            ];
        }

        return $rates;
    }
}