# Laravel Currency

[![Laravel](https://img.shields.io/badge/Laravel-9+-red)](https://laravel.com/)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-currency.svg?style=for-the)](https://github.com/fomvasss/laravel-currency)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-currency.svg)](https://packagist.org/packages/fomvasss/laravel-currency)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A Laravel package for currency conversion and exchange rate management with multiple rate providers.

## Features

- Currency conversion with buy / sell / average rates
- Multiple built-in rate providers (Monobank, PrivatBank, NBU, jsDelivr, ExchangeRatesAPI, CurrencyAPI, Fixer)
- Automatic caching of exchange rates with configurable TTL and fallback cache
- Dynamic base currency override with automatic rate recalculation
- Currency formatting with symbols, precision and locale separators
- Helper functions and Blade directives for use in views
- Custom provider support via a simple interface

## Requirements

- PHP 8.1+
- Laravel 9.x, 10.x, 11.x, 12.x, or 13.x

## Installation

```bash
composer require fomvasss/laravel-currency
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=currency-config
```

## Configuration

The published config file is located at `config/currency.php`.

### Available options

```php
return [
    // Default base currency
    'default' => 'UAH',

    // Default rate provider alias (see 'providers' array) or a fully-qualified class name.
    // An unknown alias/class throws InvalidArgumentException on boot.
    'default_provider' => env('CURRENCY_DEFAULT_PROVIDER', 'monobank'),

    // Available providers
    'providers' => [
        'nbu'              => \Fomvasss\Currency\RateProviders\NbuRateProvider::class,
        'monobank'         => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class,
        'privatbank'       => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class,
        'jsdelivr'         => \Fomvasss\Currency\RateProviders\JsDelivrProvider::class,
        'exchangeratesapi' => \Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider::class,
        'currencyapi'      => \Fomvasss\Currency\RateProviders\CurrencyApiProvider::class,
        'fixer'            => \Fomvasss\Currency\RateProviders\FixerProvider::class,
    ],

    // Primary rate cache TTL in seconds (default: 1 hour)
    'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600),

    // Fallback cache TTL in seconds — used when primary API is unavailable (default: 1 day)
    'cache_ttl_fallback' => env('CURRENCY_CACHE_TTL_FALLBACK', 86400),

    // Retry interval in seconds for an empty result — API unavailable and no fallback (default: 60)
    'cache_ttl_empty' => env('CURRENCY_CACHE_TTL_EMPTY', 60),

    // Default rate type: 'buy', 'sell', or 'average'
    'default_rate_type' => env('CURRENCY_DEFAULT_RATE_TYPE', 'average'),

    // Default decimal precision (can be overridden per currency)
    'default_precision' => env('CURRENCY_DEFAULT_PRECISION', 2),

    // API keys for paid/key-required providers
    'exchange_rates_api_key' => env('EXCHANGE_RATES_API_KEY', null),
    'currencyapi_key'        => env('CURRENCYAPI_KEY', null),
    'fixer_api_key'          => env('FIXER_API_KEY', null),

    // Active currencies with formatting options
    'currencies' => [
        'USD' => [
            'code'              => 'USD',
            'title'             => 'US Dollar',
            'symbol'            => '$',
            'precision'         => 2,
            'thousandSeparator' => ',',
            'decimalSeparator'  => '.',
            'symbolPlacement'   => 'before',
        ],
        // ... other currencies
    ],
];
```

### Environment Variables

```env
CURRENCY_DEFAULT_PROVIDER=monobank
CURRENCY_DEFAULT_RATE_TYPE=average
CURRENCY_CACHE_TTL=3600
CURRENCY_CACHE_TTL_FALLBACK=86400
CURRENCY_DEFAULT_PRECISION=2

# For paid providers (optional)
EXCHANGE_RATES_API_KEY=your_key
CURRENCYAPI_KEY=your_key
FIXER_API_KEY=your_key
```

## Rate Providers

| Alias            | Class                       | Currencies     | API Key | Notes                        |
|------------------|-----------------------------|----------------|---------|------------------------------|
| `nbu`            | `NbuRateProvider`           | 30+            | No      | National Bank of Ukraine     |
| `monobank`       | `MonobankRateProvider`      | Multiple       | No      | Monobank API                 |
| `privatbank`     | `PrivatbankRateProvider`    | EUR, USD only  | No      | PrivatBank API limitation    |
| `jsdelivr`       | `JsDelivrProvider`          | 150+           | No      | Free CDN, updated daily. Buy/sell are synthetic — a ±0.5% spread applied around the mid-market rate (`$spread`, override in a subclass). No API key required, no built-in static fallback: if the CDN is unreachable, `getRates()` returns an empty array unless a fallback cache is already populated |
| `exchangeratesapi` | `ExchangeRatesApiProvider` | Multiple       | Optional | https://exchangeratesapi.io. **Without an API key** it falls back to [frankfurter.dev](https://frankfurter.dev) (ECB rates only) — `UAH` is not an ECB currency and cannot be used as `baseCurrency` in that mode (`new ExchangeRatesApiProvider(null, 'UAH')` will get 404s); use `new ExchangeRatesApiProvider(null, 'EUR')` or supply `exchange_rates_api_key` |
| `currencyapi`    | `CurrencyApiProvider`       | Multiple       | Yes     | https://currencyapi.com (300 req/month free) |
| `fixer`          | `FixerProvider`             | Multiple       | Yes     | https://fixer.io. **Free tier is HTTP-only and `base` is locked to EUR** — this provider always requests over HTTPS, so a free-tier key will get a "base currency access restricted" error; a paid plan is required to use HTTPS and/or a non-EUR base |

> **Octane / Horizon / long-running workers:** rates are always read through `Cache` (never memoized on the provider instance), so a long-lived worker process picks up fresh rates as soon as the cache TTL expires — no per-request or per-tenant state leaks between requests.

## Basic Usage

```php
use Fomvasss\Currency\Facades\Currency;

// Convert currencies
$euros = Currency::convert(100, 'USD', 'EUR');

// Get exchange rate
$usdRate = Currency::getRate('USD');           // null -> uses config('currency.default_rate_type'), default 'average'
$buyRate  = Currency::getRate('USD', 'buy');
$sellRate = Currency::getRate('USD', 'sell');

// Get all rates
$allRates = Currency::getRates();              // null -> config default rate type
$allRates = Currency::getRates('all');         // ['USD' => ['buy' => ..., 'sell' => ...], ...]

// Format currency
$formatted = Currency::format(1234.56, 'USD'); // $ 1,234.56
$noSymbol  = Currency::format(1234.56, 'USD', false); // 1,234.56
```

`$rateType` (on `convert()`, `getRate()`, `getRates()`, and the `currency_rate()` helper) only accepts `'buy'`, `'sell'`, `'average'` (and `'all'` for `getRates()`) — anything else throws `InvalidArgumentException`.

## Switching Rate Providers

```php
// Switch by alias (recommended)
Currency::useProvider('nbu');
Currency::useProvider('monobank');
Currency::useProvider('jsdelivr');

// Switch via setRateProvider — accepts alias, class name, or instance
Currency::setRateProvider('nbu');
Currency::setRateProvider(\Fomvasss\Currency\RateProviders\NbuRateProvider::class);
Currency::setRateProvider(new NbuRateProvider());

$rate = Currency::getRate('USD');
```

## Base Currency

```php
// Get current base currency
$base = Currency::getBaseCurrency(); // 'UAH' (from config)

// Override base currency at runtime
// All rates returned by getRate() and getRates() are automatically recalculated
Currency::setBaseCurrency('USD');

$rates = Currency::getRates();
// Now returns rates relative to USD: ['EUR' => 0.92, 'UAH' => 41.5, ...]
// USD itself is NOT in the array (it is the base, rate = 1.0)

// Convert after changing base
$amount = Currency::convert(100, 'EUR', 'GBP');
```

`setBaseCurrency()` requires the current provider to have a rate for that currency — `getRates()`/`getRate()`/`convert()` throw `InvalidArgumentException` otherwise (e.g. `setBaseCurrency('JPY')` while the active provider doesn't support JPY).

## Historical Rates

Rate for a specific past date, e.g. to price an operation at the exchange rate that was
active on the day it happened:

```php
use Fomvasss\Currency\Facades\Currency;

$uah = Currency::convertAt(100, 'USD', 'UAH', '2024-01-15');   // uses the rate as of that date
$rate = Currency::getRateAt('USD', '2024-01-15');              // null -> config default rate type
$rates = Currency::getRatesAt('2024-01-15', 'all');            // ['USD' => ['buy' => ..., 'sell' => ...], ...]

Currency::supportsHistoricalRates(); // bool — whether the active provider supports getRatesAt()
```

`convertAt()`/`getRateAt()`/`getRatesAt()` accept a `DateTimeInterface`. The helper accepts a
string too:

```php
$uah = currency_convert_at(100, 'USD', 'UAH', '2024-01-15');
```

If the active provider doesn't support historical rates, all three throw `LogicException`
(check `supportsHistoricalRates()` first if the provider may vary at runtime). If the provider
has no rate for the requested currency on that date, `convertAt()`/`getRateAt()` throw
`InvalidArgumentException` — same as the current-rate methods, but with the date included in
the message (`"Currency rate not found for: USD at 2024-01-15"`).

Support by provider:

| Alias              | Historical rates | Notes                                                        |
|--------------------|-------------------|--------------------------------------------------------------|
| `nbu`              | Yes               | On a weekend/holiday, the NBU API returns the previous working day's rate under that day's date |
| `privatbank`       | Yes               | Buy/sell are present only for USD/EUR (and not always) — other currencies/dates return no rate |
| `jsdelivr`         | Yes               | Dates only from **2024-03-06** onwards; earlier dates return no rate (treated as "not found", not an error) |
| `exchangeratesapi` | Yes               | Same `UAH`-as-base limitation as the current-rate endpoint when no API key is set (frankfurter.dev, ECB currencies only) |
| `currencyapi`      | Yes               | Requires an API key |
| `fixer`            | Yes               | Requires an API key; same free-tier limitations as the current-rate endpoint |
| `monobank`         | **No**            | Monobank has no historical rates archive — `supportsHistoricalRates()` returns `false`, the historical methods throw `LogicException` |

Historical rates never use the fallback cache: a rate for another day is worse than no rate at
all, so a failed fetch (network error, error status, or "not found") returns an empty result
instead of falling back to stale cached rates. A successful result for a given date is cached
under its own key (separate from the current-rate cache) and, by default, kept **forever** —
a rate for a past date never changes. Override with `cache_ttl_historical` /
`CURRENCY_CACHE_TTL_HISTORICAL` if you'd rather expire it after a fixed number of seconds.

To backfill historical operations in bulk, group them by date first and call `getRatesAt()`
once per day instead of once per operation:

```php
foreach ($operationsByDate as $date => $operations) {
    $rates = Currency::getRatesAt($date, 'all');

    foreach ($operations as $operation) {
        // use $rates[$operation->currency] directly, no extra HTTP/cache round-trip per operation
    }
}
```

`--date=` is also available on the `currency:convert` and `currency:rates` console commands
(`php artisan currency:convert 100 usd uah --date=2024-01-15`).

## Checking Provider Capabilities

```php
$currencies = Currency::getSupportedCurrencies();      // ['USD', 'EUR', ...]
$count      = Currency::getSupportedCurrenciesCount(); // e.g. 150 for jsdelivr

if (Currency::isSupported('JPY')) {
    $rate = Currency::getRate('JPY');
}
```

## Active Currencies (from config)

```php
$currencies = Currency::getActiveCurrencies();     // full config array
$codes      = Currency::getActiveCurrencyCodes();  // ['UAH', 'USD', 'EUR', ...]
$allConfig  = Currency::getAllCurrencies();         // alias for getActiveCurrencies()

$usdConfig  = Currency::getCurrencyConfig('USD');  // ['symbol' => '$', 'precision' => 2, ...]
$precision  = Currency::getPrecision('USD');        // 2
```

## Cache Management

```php
// Clear cached rates for the current provider
Currency::clearCache();
```

Fallback cache stores the last successful rates and is used automatically when the primary API is unavailable. Configure its TTL via `cache_ttl_fallback` or the `CURRENCY_CACHE_TTL_FALLBACK` env variable.

## Helper Functions

Global PHP helpers are available without importing any class:

```php
// Convert amount
$result = currency_convert(100, 'USD', 'EUR');
$result = currency_convert(100, 'USD', 'EUR', 'sell');

// Convert using a historical rate
$result = currency_convert_at(100, 'USD', 'EUR', '2024-01-15');

// Format with symbol
$output = currency_format(1234.56, 'USD');          // $ 1,234.56
$output = currency_format(1234.56, 'USD', false);   // 1,234.56

// Get rate
$rate = currency_rate('USD');           // average
$rate = currency_rate('USD', 'buy');

// Get symbol
$symbol = currency_symbol('USD'); // '$'
```

## Blade Directives

```blade
{{-- Convert and output --}}
@currency(100, 'USD', 'EUR')

{{-- Format with symbol --}}
@currencyFormat(1234.56, 'USD')

{{-- Output exchange rate --}}
@currencyRate('USD')

{{-- Output symbol --}}
@currencySymbol('USD')
```

## Event Handling

The package dispatches a `CurrencyRateFetchFailed` event when an API call fails.

```php
// app/Listeners/HandleCurrencyRateFailure.php
namespace App\Listeners;

use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Illuminate\Support\Facades\Log;

class HandleCurrencyRateFailure
{
    public function handle(CurrencyRateFetchFailed $event): void
    {
        Log::error('Currency API failed', [
            'provider'      => $event->providerClass,
            'error'         => $event->errorMessage,
            'using_fallback' => $event->usingFallback,
        ]);
    }
}
```

Register in `EventServiceProvider`:

```php
use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use App\Listeners\HandleCurrencyRateFailure;

protected $listen = [
    CurrencyRateFetchFailed::class => [
        HandleCurrencyRateFailure::class,
    ],
];
```

**Event properties:**

| Property         | Type    | Description                             |
|------------------|---------|-----------------------------------------|
| `$providerClass` | string  | Class name of the failed provider       |
| `$errorMessage`  | string  | Error description                       |
| `$usingFallback` | bool    | Whether fallback cache is being used    |
| `$fallbackRates` | array   | Fallback rates (if available)           |

## Fallback Strategy

Use `jsdelivr` as a free fallback when primary APIs are unavailable:

```php
try {
    Currency::useProvider('monobank');
    $rate = Currency::getRate('USD');
} catch (\Exception $e) {
    Currency::useProvider('jsdelivr'); // free, 150+ currencies, no rate limits
    $rate = Currency::getRate('USD');
}
```

## Custom Providers

You can implement your own rate provider by creating a class that extends `AbstractRateProvider` or implements the `RateProvider` contract directly.

See the full guide with examples (API keys, multi-source fallback, mock for tests): [CUSTOM_PROVIDERS.md](CUSTOM_PROVIDERS.md).

## License

MIT License. See [LICENSE](LICENSE.md) for details.

## Support

If this package is useful to you, consider supporting its development:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> USDT TRC20 address: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`
