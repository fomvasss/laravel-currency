<?php

namespace Fomvasss\Currency;

use Illuminate\Support\Arr;

class Currency
{
    protected $config;

    protected $symbol = null;

    protected $userCurrency;

    /**
     * Currency constructor.
     */
    public function __construct(array $config = null)
    {
        if (!$config) {
            $config = config('currency', []);
        }

        $this->config = $config;
    }

    /**
     * @param $value
     * @param null $code
     * @param null $symbol
     * @return string
     */
    public function format($value, $code = null, $symbol = null)
    {
        $code = $this->prepareCode($code);

        $precision = $this->config("currencies.$code.precision", 0);
        $decimalSeparator = $this->config("currencies.$code.decimalSeparator", "");
        $thousandSeparator = $this->config("currencies.$code.thousandSeparator", "");

        $symbol = $symbol ?? $this->config("currencies.$code.symbol", "");

        $value = $this->prepareValue($value, $code);

        $result = number_format($value, $precision, $decimalSeparator, $thousandSeparator);

        if ($symbol) {
            return $this->config("currencies.$code.symbolPlacement") == 'after' ? $result.$symbol : $symbol.$result;
        }

        return $result;
    }

    /**
     * @param $amount
     * @param null $from
     * @param null $to
     * @param bool $format
     * @return float|int|string|null
     */
    public function convert($amount, $from = null, $to = null, $format = true)
    {
        // Get currencies involved
        $from = $from ?: $this->config('default');
        $to = $to ?: $this->getUserCurrency();

        // Get exchange rates
        $fromRate = $this->getCurrencyProp($from, 'exchangeRate');
        $toRate = $this->getCurrencyProp($to, 'exchangeRate');

        // Skip invalid to currency rates
        if ($toRate === null) {
            return null;
        }
        // Convert amount
        if ($from === $to) {
            $value = $amount;
        } else {
            $value = ($amount * $toRate) / $fromRate;
        }
        // Should the result be formatted?
        if ($format === true) {
            return $this->format($value, $to);
        }

        // Return value
        return $this->prepareValue($value, $to);
    }

    protected function prepareValue($value, $code)
    {
        if ($this->config("currencies.divide_result") && ($coin = $this->config("currencies.$code.coin", 0))) {
            $value = $value / $coin;
        }

        return $value;
    }

    /**
     * Get the given property value from provided currency.
     *
     * @param string $code
     * @param string $key
     * @param mixed  $default
     *
     * @return array
     */
    protected function getCurrencyProp($code, $key, $default = null)
    {
        return Arr::get($this->getCurrency($code), $key, $default);
    }

    /**
     * Return all currencies.
     *
     * @return array
     */
    public function getCurrencies()
    {
        return $this->config('currencies', []);
    }

    /**
     *
     * Determine if the provided currency is valid.
     *
     * @param $code
     * @return bool
     */
    public function issetCurrency($code)
    {
        return array_key_exists(strtoupper($code), $this->getCurrencies());
    }

    /**
     *
     * Determine if the provided currency is active.
     *
     * @param $code
     * @return bool
     */
    public function isActive($code)
    {
        return $code && (bool) Arr::get($this->getCurrency($code), 'active', false);
    }

    /**
     * Return the current currency.
     *
     * @param null $code
     * @return mixed
     */
    public function getCurrency($code = null)
    {
        $code = $code ?: $this->getUserCurrency();

        return Arr::get($this->getCurrencies(), strtoupper($code));
    }

    public function setUserCurrency($code)
    {
        $this->userCurrency = strtoupper($code);

        return $this;
    }

    /**
     * @return array|\Illuminate\Config\Repository|mixed
     */
    public function getUserCurrency()
    {
        return $this->userCurrency ?? $this->config('default');
    }

    /**
     * Return all active currencies.
     *
     * @return array
     */
    public function getActiveCurrencies()
    {
        return array_filter($this->getCurrencies(), function($currency) {
            return $currency['active'] == true;
        });
    }

    /**
     * Return currencies config.
     *
     * @param null $key
     * @param null $default
     * @return array|\Illuminate\Config\Repository|mixed
     */
    protected function config($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param null $code
     * @return null|string
     */
    protected function prepareCode($code = null)
    {
        if (! $code) {
            $code =  $this->config("default", 'USD');
        }

        if (array_key_exists($code, $this->getCurrencies())) {
            return $code;
        }

        return 'USD';
    }
}