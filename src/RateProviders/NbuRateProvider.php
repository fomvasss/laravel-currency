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
        $rates = ['UAH' => ['buy' => 1.0, 'sell' => 1.0]];

        foreach ($response as $item) {
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