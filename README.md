# Laravel Currency

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)

Currency conversion, exchange rates and money formatting for Laravel. Rates come from pluggable providers — Monobank, PrivatBank, NBU, jsDelivr currency-api, ExchangeRatesAPI, CurrencyAPI, Fixer, or your own — and are cached with a fallback for when the API is down.

[Українською](README_UK.md)

- **Conversion** with buy / sell / average rates
- **Seven built-in providers**, switchable at runtime, plus custom ones via a small contract
- **Historical rates** — convert at the rate of a past date
- **Base currency override** with automatic rate recalculation
- **Caching** with a long-term fallback cache and a `CurrencyRateFetchFailed` event
- **Formatting** with symbol, precision and separators per currency
- Helpers, Blade directives, `currency:rates` and `currency:convert` commands

## Requirements

- PHP ^8.1
- Laravel 9 – 13

## Installation

```bash
composer require fomvasss/laravel-currency

php artisan vendor:publish --tag=currency-config
```

```env
CURRENCY_DEFAULT_PROVIDER=monobank
```

## Quick start

```php
use Fomvasss\Currency\Facades\Currency;

Currency::convert(100, 'USD', 'UAH');          // average rate
Currency::convert(100, 'USD', 'UAH', 'sell');
Currency::getRate('EUR');                      // UAH per 1 EUR
Currency::format(1234.5, 'USD');               // "$ 1,234.50"

Currency::useProvider('nbu')->convertAt(100, 'USD', 'UAH', now()->subYear());
```

```bash
php artisan currency:rates --provider=nbu
php artisan currency:convert 100 usd eur --format
```

## Documentation

Online: **https://fomvasss.github.io/laravel-currency/** — the same pages as in [docs/](docs/index.md).

- [Installation](docs/installation.md) · [Configuration](docs/configuration.md)
- [Converting & rates](docs/usage/conversion.md) · [Rate providers](docs/usage/providers.md) · [Base currency](docs/usage/base-currency.md) · [Historical rates](docs/usage/historical-rates.md)
- [Formatting & currencies](docs/usage/formatting.md) · [Helpers & Blade](docs/usage/helpers-blade.md) · [Caching & failures](docs/usage/caching.md) · [Custom providers](docs/usage/custom-providers.md)
- Reference: [Currency facade](docs/reference/currency.md) · [Providers](docs/reference/providers.md) · [Contracts, events & manager](docs/reference/contracts.md) · [Commands](docs/reference/commands.md)
- [Upgrading](docs/upgrading.md) · [Changelog](CHANGELOG.md)

## License

MIT — see [LICENSE](LICENSE.md).

## Support

If this package is useful to you, consider supporting its development:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> USDT TRC20 address: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`
