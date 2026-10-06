# Installation

## Requirements

- PHP ^8.1
- Laravel 9 – 13 (`illuminate/support`, `illuminate/http`, `illuminate/cache`)
- A cache store — rates are always read through Laravel `Cache`

## Install

```bash
composer require fomvasss/laravel-currency
```

The service provider `Fomvasss\Currency\ServiceProvider` and the `Currency` facade alias are registered by package auto-discovery.

## Publish the config

```bash
php artisan vendor:publish --tag=currency-config
```

This copies `config/currency.php` into your app. Without publishing, the package's own defaults are merged in: base currency `UAH`, provider `monobank`, four active currencies (EUR, PLN, UAH, USD). See [Configuration](configuration.md).

The package has no migrations, no routes and no views.

## Check it works

```bash
php artisan currency:rates
php artisan currency:convert 100 usd uah
```

`currency:rates` prints the provider, the base currency and a table of buy/sell/average rates. If it prints `No rates available.`, the provider's API could not be reached — see [Caching & failures](usage/caching.md).

## Pick a provider

The default `monobank` provider needs no API key and covers 15 currencies against UAH. For a non-Ukrainian setup, or more currencies, choose another one in `.env`:

```env
CURRENCY_DEFAULT_PROVIDER=jsdelivr
```

To express rates in a currency other than UAH, change `default` in `config/currency.php` — see [Rate providers](usage/providers.md) and [Base currency](usage/base-currency.md).
