# Currency facade

`Fomvasss\Currency\Facades\Currency` → the `currency` singleton, an instance of `Fomvasss\Currency\Currency` (also bound under that class name, so it can be injected). The facade is aliased as `Currency` by auto-discovery.

Currency codes are case-insensitive everywhere. `$rateType` is `'buy'`, `'sell'`, `'average'`, or `null` for `config('currency.default_rate_type')`; `'all'` is accepted only by `getRates()`/`getRatesAt()`. An invalid type throws `InvalidArgumentException`.

## Conversion and rates

| Method | Returns | Description |
|---|---|---|
| `convert(float $amount, string $from, string $to, ?string $rateType = null)` | `float` | Converts through the base currency, rounded to the precision of `$to`. Throws `InvalidArgumentException` if a rate is missing |
| `getRate(string $currency, ?string $rateType = null)` | `?float` | Base-currency units per 1 unit of `$currency`; `1.0` for the base currency; `null` if unknown |
| `getRates(?string $rateType = null)` | `array` | `[code => float]`, or `[code => ['buy' => float, 'sell' => float]]` for `'all'`. Base currency excluded |
| `isSupported(string $currency)` | `bool` | The provider returned a rate for the currency (`false` for the provider's base currency) |
| `getSupportedCurrencies()` | `array` | Codes returned by the provider |
| `getSupportedCurrenciesCount()` | `int` | Their count |

Details — [Converting & rates](../usage/conversion.md).

## Historical rates

All throw `LogicException` if the provider doesn't implement `HistoricalRateProvider`.

| Method | Returns | Description |
|---|---|---|
| `convertAt(float $amount, string $from, string $to, DateTimeInterface\|string $date, ?string $rateType = null)` | `float` | `convert()` with rates as of `$date`. Missing rate → `InvalidArgumentException` with the date in the message |
| `getRateAt(string $currency, DateTimeInterface\|string $date, ?string $rateType = null)` | `?float` | `getRate()` as of `$date` |
| `getRatesAt(DateTimeInterface\|string $date, ?string $rateType = null)` | `array` | `getRates()` as of `$date`. In all three `*At` methods a string date is parsed with `Carbon::parse()` |
| `supportsHistoricalRates()` | `bool` | Whether the current provider implements `HistoricalRateProvider` |

Details — [Historical rates](../usage/historical-rates.md).

## Base currency

| Method | Returns | Description |
|---|---|---|
| `getBaseCurrency()` | `string` | Runtime override, else `config('currency.default')`, else the provider's base |
| `setBaseCurrency(string $currency)` | `Currency` | Override the base currency on the singleton |

Details — [Base currency](../usage/base-currency.md).

## Provider

| Method | Returns | Description |
|---|---|---|
| `useProvider(string $providerName)` | `Currency` | Switch to a provider by config alias |
| `setRateProvider(RateProvider\|string $provider)` | `Currency` | Switch by alias, class name or instance |
| `getProvider()` | `RateProvider` | Current provider instance |
| `getRateProvider()` | `RateProvider` | Alias of `getProvider()` |
| `getAvailableProviders()` | `array` | `config('currency.providers')` |
| `clearCache()` | `void` | Forget the current provider's cached, fallback and historical rates |

`useProvider()`, `setRateProvider()` and `setBaseCurrency()` mutate the shared singleton — see the warning in [Rate providers](../usage/providers.md#switching-provider).

## Formatting and currency config

| Method | Returns | Description |
|---|---|---|
| `format(float $amount, string $currency, bool $includeSymbol = true)` | `string` | `number_format()` with the currency's separators and symbol |
| `getCurrencyConfig(string $currency)` | `array` | `config('currency.currencies.CODE')`, or `[]` |
| `getActiveCurrencies()` | `array` | Configured currencies without `'active' => false` |
| `getActiveCurrencyCodes()` | `array` | Their codes |
| `getAllCurrencies()` | `array` | All configured currencies |
| `getPrecision(string $currency)` | `int` | The currency's `precision`, else `default_precision` |
| `getDefaultPrecision()` | `int` | `config('currency.default_precision')`, default `2` |

Details — [Formatting & currencies](../usage/formatting.md).

## Constructing an instance

```php
new \Fomvasss\Currency\Currency(
    RateProvider $rateProvider,
    array $config = [],                       // the 'currency' config array
    ?ProviderManager $providerManager = null, // defaults to app('currency.manager')
);
```

The `$config` array is read on every call, not merged with defaults — pass `config('currency')`.

## Helpers

Declared in `src/helpers.php`, each wrapped in `function_exists()`.

| Function | Returns | Calls |
|---|---|---|
| `currency_convert(float $amount, string $from, string $to, ?string $rateType = null)` | `float` | `convert()` |
| `currency_convert_at(float $amount, string $from, string $to, DateTimeInterface\|string $date, ?string $rateType = null)` | `float` | `convertAt()`; a string is parsed with `Carbon::parse()` |
| `currency_format(float $amount, string $currency, bool $includeSymbol = true)` | `string` | `format()` |
| `currency_rate(string $currency, ?string $rateType = null)` | `?float` | `getRate()` |
| `currency_symbol(string $currency)` | `string` | `getCurrencyConfig()['symbol']`, or the code as passed |

## Blade directives

| Directive | Compiles to |
|---|---|
| `@currency(...)` | `<?php echo app('currency')->convert(...); ?>` |
| `@currencyFormat(...)` | `<?php echo app('currency')->format(...); ?>` |
| `@currencyRate(...)` | `<?php echo app('currency')->getRate(...); ?>` |
| `@currencySymbol(...)` | `<?php echo currency_symbol(...); ?>` |

See [Helpers & Blade](../usage/helpers-blade.md).
