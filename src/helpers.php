<?php

if (! function_exists('currency_convert')) {
    /**
     * Convert amount from one currency to another.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @param string|null $rateType
     * @return float
     */
    function currency_convert(float $amount, string $from, string $to, ?string $rateType = null): float
    {
        return app('currency')->convert($amount, $from, $to, $rateType);
    }
}

if (! function_exists('currency_format')) {
    /**
     * Format amount in specified currency.
     *
     * @param float $amount
     * @param string $currency
     * @param bool $includeSymbol
     * @return string
     */
    function currency_format(float $amount, string $currency, bool $includeSymbol = true): string
    {
        return app('currency')->format($amount, $currency, $includeSymbol);
    }
}

if (! function_exists('currency_rate')) {
    /**
     * Get exchange rate for currency.
     *
     * @param string $currency
     * @param string $rateType
     * @return float|null
     */
    function currency_rate(string $currency, string $rateType = 'average'): ?float
    {
        return app('currency')->getRate($currency, $rateType);
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * Get currency symbol.
     *
     * @param string $currency
     * @return string
     */
    function currency_symbol(string $currency): string
    {
        $config = app('currency')->getCurrencyConfig($currency);
        return $config['symbol'] ?? $currency;
    }
}
