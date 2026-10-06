# Artisan commands

| Command | Description |
|---|---|
| [`currency:rates`](#currencyrates) | Show the current (or historical) rates of a provider |
| [`currency:convert`](#currencyconvert) | Convert an amount |

Both use the `currency` service, so they respect `default_provider` and `default` (base currency) from config.

## currency:rates

```bash
php artisan currency:rates
php artisan currency:rates --provider=nbu
php artisan currency:rates --currency=usd
php artisan currency:rates --provider=nbu --date=2024-01-15
php artisan currency:rates --refresh
```

| Option | Description |
|---|---|
| `--provider=` | Config alias or class name (`setRateProvider()`); an invalid one prints `Invalid provider: X` and exits with 1 |
| `--currency=` | Show one currency as a property table (code, title and symbol from config, buy, sell, average) |
| `--date=` | Historical rates for this date, parsed by `Carbon::parse()`. Exits with 1 if the provider doesn't support historical rates |
| `--refresh` | Clear the provider's current and fallback cache before fetching (not historical keys) |

Without `--currency` it prints a table of all rates (buy, sell, average to 4 decimals) and the total count, or `No rates available.` when the provider returned nothing. Rates are relative to the base currency (`default`), recalculated if it differs from the provider's base.

`--currency` for a code the provider doesn't have prints an error but still exits with 0.

## currency:convert

```bash
php artisan currency:convert 100 usd uah
php artisan currency:convert 100 usd eur --rate=sell --format
php artisan currency:convert 100 usd uah --date=2024-01-15
```

| Argument / option | Description |
|---|---|
| `amount` | Amount, cast to float |
| `from`, `to` | Currency codes, case-insensitive |
| `--rate=average` | `buy`, `sell` or `average` |
| `--date=` | Use the historical rate for this date (`convertAt()`), parsed by `Carbon::parse()` |
| `--format` | Print amounts with `format()` (symbol, separators) |

Prints the source and converted amounts, the rate type, the date (with `--date`) and the rate of `from`. Errors (`Conversion error: ...`) exit with 1.

> [!NOTE]
> `--rate` defaults to `average`, not to `default_rate_type` from config.
