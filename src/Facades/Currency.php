<?php

namespace Fomvasss\Currency\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static float convert(float $amount, string $from, string $to, ?string $rateType = null)
 * @method static float|null getRate(string $currency, string $rateType = 'average')
 * @method static array getRates(string $rateType = 'average')
 * @method static array getActiveCurrencies()
 * @method static array getActiveCurrencyCodes()
 * @method static string format(float $amount, string $currency, bool $includeSymbol = true)
 * @method static array getCurrencyConfig(string $currency)
 * @method static int getPrecision(string $currency)
 * @method static string getBaseCurrency()
 * @method static \Fomvasss\Currency\Currency setBaseCurrency(string $currency)
 * @method static \Fomvasss\Currency\Currency setRateProvider(\Fomvasss\Currency\Contracts\RateProvider|string $provider)
 * @method static \Fomvasss\Currency\Contracts\RateProvider getProvider()
 * @method static \Fomvasss\Currency\Currency useProvider(string $providerName)
 * @method static array getAvailableProviders()
 * @method static array getSupportedCurrencies()
 * @method static int getSupportedCurrenciesCount()
 * @method static bool isSupported(string $currency)
 * @method static array getAllCurrencies()
 *
 * @see \Fomvasss\Currency\Currency
 */
class Currency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'currency';
    }
}
