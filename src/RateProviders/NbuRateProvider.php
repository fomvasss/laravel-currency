<?php

namespace Fomvasss\Currency\RateProviders;

class NbuRateProvider extends AbstractRateProvider
{
    protected function getApiUrl(): string
    {
        return 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
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