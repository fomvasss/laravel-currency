# Laravel Currency

Currency conversion, exchange rates and money formatting for Laravel. Rates come from pluggable providers — Ukrainian banks (Monobank, PrivatBank, NBU) and international APIs (jsDelivr currency-api, ExchangeRatesAPI / frankfurter, CurrencyAPI, Fixer) — and are cached with a fallback for when the API is down.

- **Conversion** with buy / sell / average rates
- **Seven built-in providers**, switchable at runtime, plus your own via a small contract
- **Historical rates** — convert at the rate of a past date
- **Base currency override** — rates recalculated relative to any currency the provider knows
- **Caching** with a long-term fallback cache and a `CurrencyRateFetchFailed` event for alerting
- **Formatting** — symbol, precision, separators per currency
- Global **helpers**, **Blade directives** and two **Artisan commands**

## Quick example

```php
use Fomvasss\Currency\Facades\Currency;

Currency::convert(100, 'USD', 'UAH');          // 100 USD in UAH at the average rate
Currency::convert(100, 'USD', 'UAH', 'sell');  // at the bank's selling rate
Currency::getRate('EUR');                      // UAH per 1 EUR
Currency::format(1234.5, 'USD');               // "$ 1,234.50"

Currency::useProvider('nbu')
    ->convertAt(100, 'USD', 'UAH', now()->subYear()); // rate as of a year ago
```

```bash
php artisan currency:rates --provider=nbu
php artisan currency:convert 100 usd eur --format
```

## Contents

Getting started

1. [Installation](installation.md)
2. [Configuration](configuration.md)

Usage

3. [Converting & rates](usage/conversion.md)
4. [Rate providers](usage/providers.md)
5. [Base currency](usage/base-currency.md)
6. [Historical rates](usage/historical-rates.md)
7. [Formatting & currencies](usage/formatting.md)
8. [Helpers & Blade](usage/helpers-blade.md)
9. [Caching & failures](usage/caching.md)
10. [Custom providers](usage/custom-providers.md)

Reference

11. [Currency facade](reference/currency.md)
12. [Providers](reference/providers.md)
13. [Contracts, events & manager](reference/contracts.md)
14. [Artisan commands](reference/commands.md)

[Upgrading](upgrading.md)
