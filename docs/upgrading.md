# Upgrading

Changes that can affect existing code. The full list is in the [CHANGELOG](https://github.com/fomvasss/laravel-currency/blob/master/CHANGELOG.md).

After an upgrade, compare your published `config/currency.php` with the package's — new keys fall back to package defaults, but a published file never gets them written in:

```bash
php artisan vendor:publish --tag=currency-config --force   # overwrites; back up your file first
```

## 2.7.5

Providers outside the package (your own, or subclasses of built-in ones) are cached under their full class name: `currency_rates_App_Currency_MyProvider` instead of `currency_rates_MyProvider`. Their rates, fallback copy and historical rates are fetched again once after the upgrade; if the API is down right then, there is no fallback copy yet. Built-in providers keep their keys.

## 2.7.4

- Historical rates are cached under new keys (`currency_rates_{ProviderClass}_{generation}_{Y-m-d}`), so every date is fetched once more after the upgrade. The old `currency_rates_{ProviderClass}_{Y-m-d}` keys are no longer read; on a store without eviction remove them by hand.
- `clearCache()` and `currency:rates --refresh` now drop historical rates too.
- An empty `CURRENCY_CACHE_TTL_HISTORICAL=` caches forever, as `null` does. Before, it disabled the historical cache.

## 2.7.1

`exchangeratesapi`, `currencyapi` and `fixer` stored rates in the opposite direction (1 USD = 0.024 UAH), so `convert(100, 'USD', 'UAH')` returned `2.4` instead of `4150`. They now store "UAH per 1 unit of the currency", like the other providers.

- Inverted rates already in the cache are served until they expire — the current ones for `cache_ttl`, the fallback copy for `cache_ttl_fallback`, and historical ones forever (default `cache_ttl_historical = null`). After deploying, remove the keys starting with `currency_rates_ExchangeRatesApiProvider`, `currency_rates_CurrencyApiProvider`, `currency_rates_FixerProvider`, or run `php artisan cache:clear` if the store holds nothing else valuable. `currency:rates --refresh` is not enough: it does not touch per-date keys.
- If you worked around the bug with a subclass that inverts the rates, remove it, otherwise the rates get inverted twice.

## 2.7

- New historical rates API (`convertAt()`, `getRateAt()`, `getRatesAt()`, `supportsHistoricalRates()`, `currency_convert_at()`), `--date=` on both commands, `cache_ttl_historical` config key. Nothing to change unless you have custom providers:
  - to support historical rates, a provider must `implements HistoricalRateProvider` and override `getHistoricalApiUrl()` — see [Custom providers](usage/custom-providers.md#adding-historical-rate-support).
- `CurrencyRateFetchFailed` has a new last constructor argument and property `?DateTimeInterface $date`. Code that constructs the event itself keeps working.

## 2.6

- An unknown provider — in `useProvider()`, `setRateProvider()` or `default_provider` — throws `InvalidArgumentException` instead of silently using Monobank. Check `CURRENCY_DEFAULT_PROVIDER`.
- `$rateType` is validated: anything other than `buy`, `sell`, `average` (and `all` for `getRates()`) throws `InvalidArgumentException` instead of meaning `average`. This includes an invalid `CURRENCY_DEFAULT_RATE_TYPE`.
- `getRate()`, `getRates()` and `currency_rate()` take `?string $rateType = null`; `null` now means `default_rate_type` from config (was a hard-coded default).
- `getRates()` throws `InvalidArgumentException` when the base currency set with `setBaseCurrency()` has no rate in the provider (it used to return rates in the provider's base silently).
- `jsdelivr` no longer returns hard-coded static rates when the CDN is down — you get the fallback-cached rates or nothing.
- NBU no longer includes `UAH` in its rates; `getRate('UAH')` still returns `1.0`.
- The `currency.providers` container binding was removed — use `useProvider()` / `setRateProvider()` or `currency.manager`.
- `cache_ttl` from config is now honoured (rates were always cached for 1 hour), and a new `cache_ttl_empty` (60 s) controls how often a failing API is retried.
- `currency:rates --refresh` clears the selected provider's cache (respects `--provider`).

## 2.5

- Currencies with `'active' => false` are excluded from `getActiveCurrencies()` / `getActiveCurrencyCodes()`.
- `jsdelivr` uses `UAH` as base currency (was `EUR`) — with `default => 'UAH'` rates no longer need recalculation; with another `default` they are recalculated through UAH.

## 2.2

- `clearCache()` was added to the `RateProvider` interface — custom providers that implement the interface directly (not via `AbstractRateProvider`) must add it.

## 2.1

- PHP 8.1 is required.

## 1.x → 2.x

2.0 is a rewrite: exchange rates come from providers instead of the config file.

**Requirements:** PHP ^8.1, Laravel 9+ (1.x supported Laravel 5.8 – 7).

**Config.** Republish it — the format changed:

```bash
php artisan vendor:publish --tag=currency-config --force
```

| 1.x | 2.x |
|---|---|
| `'default' => 'USD'` | `'default' => 'UAH'` — now the base currency of the rates |
| `divide_result` | removed |
| `currencies.*.exchangeRate` | removed — rates come from `default_provider` |
| `currencies.*.coin` | removed |
| `currencies.*.format` | removed |
| `currencies.*.active` (default `false`) | optional; a listed currency is active unless `'active' => false` |
| — | `default_provider`, `providers`, `cache_ttl*`, `default_rate_type`, `default_precision`, API keys |

The publish tag changed from `config` to `currency-config`.

**Rate direction.** 1.x `exchangeRate` meant "units of the currency per 1 `default`" and converted as `amount × to ÷ from`. 2.x rates are "units of the base per 1 unit of the currency" and convert as `amount × from ÷ to`. If you stored rates of your own, invert them.

**API.**

| 1.x | 2.x |
|---|---|
| `convert($amount, $from = null, $to = null, $format = true)` — formatted string by default, `null` for a missing rate | `convert(float $amount, string $from, string $to, ?string $rateType = null): float` — always a float, throws for a missing rate. Format with `format()` |
| `format($value, $code = null, $symbol = null)` — no space between symbol and number, 0 decimals by default | `format(float $amount, string $currency, bool $includeSymbol = true)` — symbol separated by a space, 2 decimals by default |
| `getCurrencies()` | `getAllCurrencies()` |
| `getCurrency($code = null)` | `getCurrencyConfig(string $currency)` (returns `[]` instead of `null`) |
| `issetCurrency($code)` | `getCurrencyConfig($code) !== []`, or `isSupported($code)` for "the provider has a rate" |
| `isActive($code)` | `in_array($code, getActiveCurrencyCodes())` |
| `setUserCurrency()` / `getUserCurrency()` | removed — keep the user's currency in your app (session, user model) |
| `getActiveCurrencies()` | same name; inactive now means `'active' => false` |
