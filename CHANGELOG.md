# Changelog Laravel Currency

All notable changes to `laravel-currency` will be documented in this file.

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
- PHP 8.0+ support
- Rate provider interface for custom implementations
- Dynamic rate provider switching
- Facade for convenient access
- Extensive configuration options

### Changed
- Improved API design for better usability
- Better error handling
- Enhanced documentation with examples
- Updated dependencies to support modern Laravel versions

### Fixed
- Various bug fixes and improvements

## 1.0.0 - 2019-11-26

- Initial release
