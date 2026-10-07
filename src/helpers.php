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

if (! function_exists('currency_convert_at')) {
    /**
     * Convert amount from one currency to another using rates as of a specific date.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @param \DateTimeInterface|string $date
     * @param string|null $rateType
     * @return float
     */
    function currency_convert_at(float $amount, string $from, string $to, \DateTimeInterface|string $date, ?string $rateType = null): float
    {
        $date = is_string($date) ? \Illuminate\Support\Carbon::parse($date) : $date;

        return app('currency')->convertAt($amount, $from, $to, $date, $rateType);
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
     * @param string|null $rateType
     * @return float|null
     */
    function currency_rate(string $currency, ?string $rateType = null): ?float
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
