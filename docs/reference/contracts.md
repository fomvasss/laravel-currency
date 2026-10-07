# Contracts, events & manager

## RateProvider

`Fomvasss\Currency\Contracts\RateProvider` — what the `Currency` service needs from a provider.

| Method | Returns | Description |
|---|---|---|
| `getRates()` | `array` | `['USD' => ['buy' => float, 'sell' => float], ...]` — base-currency units per 1 unit of the currency |
| `getRate(string $currency)` | `?array` | `['buy' => float, 'sell' => float]` or `null` |
| `supports(string $currency)` | `bool` | Whether `getRates()` contains the currency |
| `getBaseCurrency()` | `string` | The currency the rates are expressed in |
| `getSupportedCurrencies()` | `array` | Codes from `getRates()` |
| `getSupportedCurrenciesCount()` | `int` | Their count |
| `clearCache()` | `void` | Forget cached rates (current and fallback) |

## HistoricalRateProvider

`Fomvasss\Currency\Contracts\HistoricalRateProvider extends RateProvider` — opt-in support for the `*At` methods of `Currency`.

| Method | Returns | Description |
|---|---|---|
| `getRatesAt(DateTimeInterface $date)` | `array` | Same shape as `getRates()`, as of `$date` |
| `getRateAt(string $currency, DateTimeInterface $date)` | `?array` | One currency as of `$date` |

`Currency::supportsHistoricalRates()` is an `instanceof HistoricalRateProvider` check. `AbstractRateProvider` already has both methods; a subclass only declares `implements HistoricalRateProvider` and overrides `getHistoricalApiUrl()` — see [Custom providers](../usage/custom-providers.md#adding-historical-rate-support).

## CurrencyRateFetchFailed

`Fomvasss\Currency\Events\CurrencyRateFetchFailed`, dispatched by `AbstractRateProvider` when the API fails.

| Property | Type | Description |
|---|---|---|
| `providerClass` | `string` | FQCN of the provider |
| `errorMessage` | `string` | Exception message, `API returned error status: N`, `API returned a non-array response`, `Using fallback cached rates` or `No cached rates available, using static fallback` |
| `usingFallback` | `bool` | `true` when fallback (cached or static) rates are being served |
| `fallbackRates` | `?array` | The rates served instead; `[]` when the static fallback is empty |
| `date` | `?DateTimeInterface` | The requested date for historical fetches, else `null` |

When and how often it fires — [Caching & failures](../usage/caching.md#the-currencyratefetchfailed-event).

## ProviderManager

`Fomvasss\Currency\ProviderManager`, the `currency.manager` singleton; facade `Fomvasss\Currency\Facades\CurrencyProvider` (not auto-aliased).

| Method | Returns | Description |
|---|---|---|
| `resolve(RateProvider\|string $provider)` | `RateProvider` | An instance as is; a config alias; or a class name. Used by `setRateProvider()` and for `default_provider` |
| `createProvider(string $name)` | `RateProvider` | New instance by config alias only. Used by `useProvider()` |
| `provider(?string $name = null)` | `RateProvider` | `createProvider($name ?? default_provider)` — so `default_provider` must be an alias here, not a class name |
| `getAvailableProviders()` | `array` | `config('currency.providers')` |
| `getDefaultDriver()` | `string` | `config('currency.default_provider')` |
| any other method | mixed | Forwarded to `provider()` — a new instance of the default provider on each call |

Instances are created with the container (`$container->make($class)`); a class that doesn't exist or doesn't implement `RateProvider` throws `InvalidArgumentException`. Every call creates a new instance.

> [!NOTE]
> The manager copies `config('currency')` when it is first resolved. Changing `currency.providers` or `currency.default_provider` with `config([...])` after that has no effect on it; the same goes for the `Currency` singleton, which keeps its own copy of the config (base currency, precision, currencies, default rate type).

## Container bindings

| Abstract | Concrete |
|---|---|
| `currency` (alias `Fomvasss\Currency\Currency`) | singleton `Currency` with `default_provider` |
| `currency.manager` | singleton `ProviderManager` |

## Service provider

`Fomvasss\Currency\ServiceProvider`: merges `config/currency.php`, registers the bindings and the Blade directives; in the console also the `currency-config` publish tag and the [Artisan commands](commands.md).
