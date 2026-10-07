# Formatting & currencies

## format()

```php
Currency::format(float $amount, string $currency, bool $includeSymbol = true): string
```

```php
Currency::format(1234.56, 'USD');         // "$ 1,234.56"
Currency::format(1234.56, 'USD', false);  // "1,234.56"
Currency::format(1234.56, 'UAH');         // "1 234,56 ₴"
Currency::format(1234.56, 'EUR');         // "€ 1 234,56"
Currency::format(1234.56, 'XYZ');         // "XYZ 1,234.56" — not in config
```

(Separators as in the default config.)

`format()` only formats — it doesn't convert. Combine the two for prices in another currency:

```php
Currency::format(Currency::convert($price, 'UAH', 'USD'), 'USD');
```

How the string is built, from the currency's entry in `currencies`:

1. `number_format($amount, precision, decimalSeparator, thousandSeparator)`
2. with the symbol: `symbol . ' ' . number` for `symbolPlacement = before`, `number . ' ' . symbol` for `after` — always one space between them

| Field | When missing from the currency's config |
|---|---|
| `precision` | `default_precision` |
| `thousandSeparator` | `,` |
| `decimalSeparator` | `.` |
| `symbol` | the currency code |
| `symbolPlacement` | `before` |

The amount is formatted by PHP's `number_format()`: no locale, no negative-number styling (`-5` gives `$ -5.00`). For locale-aware output use PHP `intl` (`NumberFormatter`) and keep this package for the rates.

## Currencies from config

```php
Currency::getActiveCurrencies();     // currencies without 'active' => false, keyed by code
Currency::getActiveCurrencyCodes();  // ['EUR', 'PLN', 'UAH', 'USD']
Currency::getAllCurrencies();        // the whole 'currencies' array, including 'active' => false
Currency::getCurrencyConfig('usd');  // ['code' => 'USD', 'symbol' => '$', ...] or [] if not configured
```

Every currency listed in `currencies` is active unless it has `'active' => false`. The list is your app's catalogue (e.g. for a currency switcher); it doesn't limit which currencies can be converted.

## Precision

```php
Currency::getPrecision('USD');       // the currency's 'precision', else default_precision
Currency::getDefaultPrecision();     // config('currency.default_precision'), 2
```

`getPrecision()` is what `convert()` rounds the result to.

## Symbols

```php
currency_symbol('USD');  // '$', or the code itself for a currency not in config
```

See [Helpers & Blade](helpers-blade.md).
