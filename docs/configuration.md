# Configuration

All settings live in `config/currency.php` (publish it with `php artisan vendor:publish --tag=currency-config`).

## Keys

| Key | Env | Default | Description |
|---|---|---|---|
| `default` | — | `'UAH'` | Base currency of the [Currency facade](reference/currency.md): what `getRate()` and `getRates()` are expressed in. If it differs from the provider's own base, rates are recalculated, see [Base currency](usage/base-currency.md) |
| `default_provider` | `CURRENCY_DEFAULT_PROVIDER` | `'monobank'` | Provider used by the `Currency` facade: an alias from `providers` or a fully-qualified class name implementing `RateProvider` |
| `providers` | — | 7 built-in aliases | Map of alias → class, used by `useProvider()`, `setRateProvider()` and `currency:rates --provider` |
| `cache_ttl` | `CURRENCY_CACHE_TTL` | `3600` | Seconds a fetched set of current rates is cached |
| `cache_ttl_fallback` | `CURRENCY_CACHE_TTL_FALLBACK` | `86400` | Seconds the last successful rates are kept as a fallback for when the API fails |
| `cache_ttl_empty` | `CURRENCY_CACHE_TTL_EMPTY` | `60` | Seconds a failed fetch is cached before the next retry — empty rates, or fallback/static rates served instead |
| `cache_ttl_historical` | `CURRENCY_CACHE_TTL_HISTORICAL` | `null` | Seconds rates for a past date are cached; `null` or empty caches them forever. Today's and future dates use `cache_ttl` |
| `default_rate_type` | `CURRENCY_DEFAULT_RATE_TYPE` | `'average'` | Rate type used when a method gets `$rateType = null`: `buy`, `sell` or `average` |
| `default_precision` | `CURRENCY_DEFAULT_PRECISION` | `2` | Decimal places for `convert()`, `format()` and `getPrecision()` when the currency has no own `precision` |
| `exchange_rates_api_key` | `EXCHANGE_RATES_API_KEY` | `null` | Key for `exchangeratesapi`; without it the provider uses frankfurter.dev |
| `currencyapi_key` | `CURRENCYAPI_KEY` | `null` | Key for `currencyapi` (required) |
| `fixer_api_key` | `FIXER_API_KEY` | `null` | Key for `fixer` (required) |
| `currencies` | — | EUR, PLN, UAH, USD | Currencies known to your app, with formatting options |

> [!NOTE]
> `default_provider` is resolved lazily — the first time the `currency` service is used (a facade call, a helper, a command). An unknown alias or a class that doesn't exist throws `InvalidArgumentException` at that point, not at boot.

> [!WARNING]
> Leave `CURRENCY_CACHE_TTL_HISTORICAL` out of `.env` (or set it to `null`) to cache historical rates forever. An empty value (`CURRENCY_CACHE_TTL_HISTORICAL=`) is the string `''`, which Laravel treats as a zero TTL — historical rates are then not cached at all.

## Providers

```php
'default_provider' => env('CURRENCY_DEFAULT_PROVIDER', 'monobank'),

'providers' => [
    'nbu' => \Fomvasss\Currency\RateProviders\NbuRateProvider::class,
    'monobank' => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class,
    'privatbank' => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class,
    'jsdelivr' => \Fomvasss\Currency\RateProviders\JsDelivrProvider::class,
    'exchangeratesapi' => \Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider::class,
    'currencyapi' => \Fomvasss\Currency\RateProviders\CurrencyApiProvider::class,
    'fixer' => \Fomvasss\Currency\RateProviders\FixerProvider::class,
],
```

Add your own provider under any alias — see [Custom providers](usage/custom-providers.md). Provider classes are built through the service container, so constructor arguments can be configured with container bindings, see [Rate providers](usage/providers.md#constructor-arguments).

## Caching

```php
'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600),
'cache_ttl_fallback' => env('CURRENCY_CACHE_TTL_FALLBACK', 86400),
'cache_ttl_empty' => env('CURRENCY_CACHE_TTL_EMPTY', 60),
'cache_ttl_historical' => env('CURRENCY_CACHE_TTL_HISTORICAL'),
```

How the four interact — [Caching & failures](usage/caching.md).

## Rates and precision

```php
'default_rate_type' => env('CURRENCY_DEFAULT_RATE_TYPE', 'average'),
'default_precision' => env('CURRENCY_DEFAULT_PRECISION', 2),
```

- `buy` — the bank's buying rate (you sell foreign currency to the bank)
- `sell` — the bank's selling rate (you buy foreign currency from the bank)
- `average` — `(buy + sell) / 2`

An invalid `default_rate_type` is not checked at boot; every call that falls back to it throws `InvalidArgumentException: Invalid rate type`.

## Currencies

```php
'currencies' => [
    'USD' => [
        'code' => 'USD',
        'title' => 'US Dollar',
        'symbol' => '$',
        'precision' => 2,
        'thousandSeparator' => ',',
        'decimalSeparator' => '.',
        'symbolPlacement' => 'before',
    ],
    // ...
],
```

| Field | Used by | Fallback when missing |
|---|---|---|
| `code` | informational | — |
| `title` | `currency:rates --currency=X` | `N/A` |
| `symbol` | `format()`, `currency_symbol()`, `@currencySymbol` | the currency code |
| `precision` | `format()`, `convert()` rounding, `getPrecision()` | `default_precision` |
| `thousandSeparator` | `format()` | `,` |
| `decimalSeparator` | `format()` | `.` |
| `symbolPlacement` | `format()`: `before` or `after` | `before` |
| `active` | `getActiveCurrencies()` — `false` excludes the currency | `true` |

The array key is what lookups use (`getCurrencyConfig('usd')` upper-cases the code and reads `currencies.USD`). The published config contains ~25 more currencies commented out — uncomment the ones you need.

The `currencies` list is independent of the provider: a currency doesn't have to be listed here to be converted, and listing it doesn't make the provider return a rate for it.

## Environment example

```env
CURRENCY_DEFAULT_PROVIDER=monobank
CURRENCY_DEFAULT_RATE_TYPE=average
CURRENCY_DEFAULT_PRECISION=2
CURRENCY_CACHE_TTL=3600
CURRENCY_CACHE_TTL_FALLBACK=86400
CURRENCY_CACHE_TTL_EMPTY=60
# CURRENCY_CACHE_TTL_HISTORICAL=2592000

# key-based providers
EXCHANGE_RATES_API_KEY=
CURRENCYAPI_KEY=
FIXER_API_KEY=
```
