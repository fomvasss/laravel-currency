# Changelog Laravel Currency

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## 2.8.1 - 2026-10-07

### Fixed
- `currency` and `currency.manager` were singletons, so under Octane or in a queue worker `setBaseCurrency()`, `useProvider()` and `setRateProvider()` stayed in effect for every later request or job of that worker, and config changes were never picked up. Both are now scoped bindings, fresh for each request and job

## 2.8.0 - 2026-10-07

### Added
- `convertAt()`, `getRateAt()` and `getRatesAt()` accept the date as a string (parsed with `Carbon::parse()`), like `currency_convert_at()`. Before, a string threw a `TypeError`

## 2.7.6 - 2026-10-07

### Fixed
- A 2xx response without rates (Fixer reports an invalid key as HTTP 200 with `"success": false`) returned empty rates with no `CurrencyRateFetchFailed` event and without trying the fallback cache. It is handled as a failure now

## 2.7.5 - 2026-10-07

### Fixed
- Two providers with the same short class name shared one cache — for example an app provider extending `NbuRateProvider` under the same name served the parent's cached rates. Providers outside the package are now cached under their full class name; built-in providers keep their keys

## 2.7.4 - 2026-10-07

### Fixed
- Historical rates for today (or a future date) were cached forever, so a rate requested before the bank published it stayed wrong. They are now cached for `cache_ttl`
- An empty `CURRENCY_CACHE_TTL_HISTORICAL=` disabled the historical cache (TTL 0); it now means "forever", like `null`
- `clearCache()` and `currency:rates --refresh` did not drop historical rates. They do now. The per-date cache key changed, so each date is fetched once more after upgrading

## 2.7.3 - 2026-10-07

### Fixed
- Fallback and static rates served while the API was failing were cached for the full `cache_ttl`, so stale rates stayed for up to an hour after the API recovered. They are now cached for `cache_ttl_empty` and the API is retried after it

## 2.7.2 - 2026-10-07

### Fixed
- `format()` used 2 decimals for a currency without its own `precision` instead of `default_precision`, so it disagreed with `convert()` and `getPrecision()`

## 2.7.1 - 2026-10-07

### Fixed
- `exchangeratesapi`, `currencyapi` and `fixer` providers returned inverted rates (units of the currency per 1 base unit), so conversions with them were wrong by the square of the rate (`convert(100, 'USD', 'UAH')` gave `2.4` instead of `4150`). Rates are now "base units per 1 unit of the currency", like the other providers. Cached rates of these providers, including historical per-date keys, must be cleared after upgrading — see the upgrade guide. Remove any subclass that inverted the rates as a workaround

## 2.7.0 - 2026-08-23

### Added
- Historical (per-date) exchange rates: `Currency::convertAt()`, `getRateAt()`, `getRatesAt()`, `supportsHistoricalRates()`, and the `currency_convert_at()` helper.
- `--date=` option on `currency:convert` and `currency:rates` console commands.
- `HistoricalRateProvider` contract for provider authors, and support for it in the `nbu`, `privatbank`, `jsdelivr`, `exchangeratesapi`, `currencyapi` and `fixer` providers (`monobank` has no historical archive and does not implement it).
- `cache_ttl_historical` config option (`CURRENCY_CACHE_TTL_HISTORICAL`) — TTL for cached historical rates; `null` (default) caches them forever, since a rate for a past date never changes. Historical rates are cached under their own per-date key and never use the fallback cache.

### Changed
- `CurrencyRateFetchFailed` event gained a `?\DateTimeInterface $date` property (last constructor argument, defaults to `null`), set when the failure happened while fetching a historical rate.

## 2.6.0 - 2026-08-23

### Added
- `cache_ttl_empty` config option (`CURRENCY_CACHE_TTL_EMPTY`, default 60s) — how long an empty result (API unavailable, no fallback rates) is kept before the next request retries the API.

### Changed
- `jsdelivr` provider no longer falls back to hardcoded static rates when the CDN is unavailable — it returns no rates (or the fallback-cached ones) instead of stale numbers.
- NBU provider no longer includes the base currency (`UAH`) in its rates, consistent with the other providers; `getRate()`/`getRates()` still return `1.0` for the base currency.
- `useProvider()`, `setRateProvider()` and the `default_provider` config value throw `InvalidArgumentException` for an unknown provider instead of silently falling back to Monobank.
- `$rateType` on `convert()`, `getRate()`, `getRates()` and `currency_rate()` is validated: only `buy`, `sell`, `average` (and `all` for `getRates()`) are accepted; anything else throws `InvalidArgumentException` instead of silently using `average`.
- `getRates()` throws `InvalidArgumentException` when the custom base currency (`setBaseCurrency()`) has no rate in the current provider, instead of silently returning rates in the provider's own base.
- `getRate()`, `getRates()` and `currency_rate()` accept `?string $rateType = null`; `null` resolves to `default_rate_type` from config, consistent with `convert()`.
- `currency:rates --refresh` clears the cache of the selected provider (respects `--provider`) instead of a hardcoded list.
- Fixer endpoint updated to `data.fixer.io/api/latest`; frankfurter fallback of `exchangeratesapi` updated to `api.frankfurter.dev/v1`. Both services' limitations (EUR-only base on Fixer free tier, no UAH base on frankfurter) are documented in README.

### Fixed
- Monobank provider never returned the AUD rate.
- Providers skip entries with a missing/zero rate instead of returning `0`, which could cause a division-by-zero error in `convert()`.
- `currency:rates --currency=X` crashed.
- `cache_ttl` from config was ignored (rates were always cached for 1 hour).
- Long-running workers (Octane, Horizon) never picked up fresh rates after the cache TTL expired.
- A non-JSON API response caused a runtime error instead of falling back to cached rates.

### Removed
- `currency.providers` container binding — resolve providers via `useProvider()`/`setRateProvider()` or the `currency.manager` service.

## 2.5.1 - 2026-05-27

### Changed
- Relaxed composer version constraints (`^12` instead of `^12.0` etc.).

## 2.5.0 - 2026-05-03

### Added
- `getRateProvider()` alias for `getProvider()`.
- Currencies with `'active' => false` in config are excluded from `getActiveCurrencies()`/`getActiveCurrencyCodes()`.

### Fixed
- `jsdelivr` provider now uses `UAH` as base currency, consistent with the other providers (was `EUR`).

## 2.4.0 - 2026-05-02

### Changed
- README rewritten in English, Ukrainian README (`README_UK.md`) added, `CUSTOM_PROVIDERS.md` rewritten in English.

## 2.3.0 - 2026-05-02

### Added
- Laravel 13 support.

## 2.2.0 - 2026-01-25

### Added
- Long-term fallback cache: the last successful rates are stored for `cache_ttl_fallback` (`CURRENCY_CACHE_TTL_FALLBACK`, default 1 day) and served when the API is unavailable.
- `clearCache()` on `Currency` and on rate providers (also added to the `RateProvider` interface).
- `CurrencyRateFetchFailed` event, dispatched on API failure and when fallback rates are used — for custom alerting/monitoring.

## 2.1.0 - 2026-01-23

### Added
- `default_precision` config option (`CURRENCY_DEFAULT_PRECISION`, default 2) used when a currency has no own `precision`; `getDefaultPrecision()` method.

### Security
- Minimum PHP version raised to 8.1.

## 2.0.0 - 2026-01-21

### Added
- Complete rewrite of the package with modern architecture
- Multiple rate providers support (Monobank, PrivatBank, NBU, jsDelivr, ExchangeRatesAPI)
- **jsDelivr CDN provider** - Free provider with 150+ currencies via CDN (great fallback option)
- Currency conversion with buy/sell/average rates
- **Configurable default rate type** - Set global default rate type (buy/sell/average) in config
- **Enhanced setRateProvider method** - Supports provider aliases, class names, and instances
- **Dynamic base currency override** - setBaseCurrency() method to override config base currency
- **Automatic rate conversion** - When base currency changes, all rates are recalculated automatically
- **Provider capability methods** - getSupportedCurrencies() and getSupportedCurrenciesCount()
- Automatic rate caching with configurable TTL
- Formatted currency output with locale support
- Active currencies management
- Comprehensive test coverage
- Laravel 9-12 support
- PHP 8.1+ support
- Rate provider interface for custom implementations
- Dynamic rate provider switching
- Facade for convenient access
- Extensive configuration options

### Changed
- Improved API design for better usability
- Better error handling
- Enhanced documentation with examples
- Updated dependencies to support modern Laravel versions
- **Optimized currency configuration** - Removed unused fields (exchangeRate, format, coin, active)
- **Simplified active currency management** - All currencies in config are active by default
- **Updated minimum PHP version to 8.1+** - Security update to avoid CVE-2025-64500

### Fixed
- Various bug fixes and improvements

## 1.0.0 - 2019-11-26

- Initial release
