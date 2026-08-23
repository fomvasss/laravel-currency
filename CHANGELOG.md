# Changelog Laravel Currency

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

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
