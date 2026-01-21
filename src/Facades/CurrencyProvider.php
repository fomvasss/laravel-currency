<?php

namespace Fomvasss\Currency\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fomvasss\Currency\Contracts\RateProvider provider(?string $name = null)
 * @method static \Fomvasss\Currency\Contracts\RateProvider createProvider(string $name)
 * @method static array getAvailableProviders()
 * @method static string getDefaultDriver()
 *
 * @see \Fomvasss\Currency\ProviderManager
 */
class CurrencyProvider extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'currency.manager';
    }
}
