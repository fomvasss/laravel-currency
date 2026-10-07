# Converting & rates

```php
use Fomvasss\Currency\Facades\Currency;
```

The facade resolves the `currency` singleton (`Fomvasss\Currency\Currency`), so you can also inject `Fomvasss\Currency\Currency` or call `app('currency')`.

## How rates are expressed

A rate is the amount of the **base currency** for one unit of the currency. With the default base `UAH` and Monobank:

```php
Currency::getRate('USD'); // ~41.5 — UAH per 1 USD
```

Every provider returns rates as `['USD' => ['buy' => float, 'sell' => float], ...]` against its own base currency; `Currency` reduces that pair to one number by the rate type and, when your base currency differs from the provider's, recalculates it — see [Base currency](base-currency.md).

## Rate types

| Type | Value |
|---|---|
| `buy` | Bank buying rate — you sell the currency |
| `sell` | Bank selling rate — you buy the currency |
| `average` | `(buy + sell) / 2` |
| `all` | Only for `getRates()` / `getRatesAt()`: the raw `['buy' => ..., 'sell' => ...]` pair |

Passing `null` (the default everywhere) uses `default_rate_type` from config. Anything else throws `InvalidArgumentException: Invalid rate type: ...`.

Providers without a buy/sell split (NBU, ExchangeRatesAPI, CurrencyAPI, Fixer) return the same value for both, so all three types give the same number. jsDelivr returns a synthetic ±0.5% spread around the mid rate.

## convert()

```php
Currency::convert(float $amount, string $from, string $to, ?string $rateType = null): float
```

```php
Currency::convert(100, 'USD', 'UAH');          // 100 × rate(USD)
Currency::convert(4150, 'UAH', 'USD');         // 4150 ÷ rate(USD)
Currency::convert(100, 'usd', 'eur', 'buy');   // codes are case-insensitive
```

Conversion goes through the base currency: `$amount × rate($from) ÷ rate($to)`, where the rate of the base currency itself is `1`. The **same rate type is used on both legs**, so `convert(100, 'USD', 'EUR', 'buy')` uses the buying rate of USD and the buying rate of EUR — it is not a "sell USD, buy EUR" cross rate.

The result is rounded to the target currency's `precision`, or `default_precision` if the currency isn't in `currencies` config. `convert(100, 'USD', 'USD')` skips the provider entirely and just rounds.

If the provider has no rate for `$from` or `$to`, `convert()` throws `InvalidArgumentException: Currency rate not found for: XXX`. This includes the case where the API is unreachable and nothing is cached — see [Caching & failures](caching.md).

## getRate()

```php
Currency::getRate(string $currency, ?string $rateType = null): ?float
```

```php
Currency::getRate('USD');          // config default type
Currency::getRate('USD', 'sell');
Currency::getRate('UAH');          // 1.0 — the base currency
Currency::getRate('XYZ');          // null — unknown to the provider
```

Unlike `convert()`, `getRate()` returns `null` instead of throwing when there is no rate.

## getRates()

```php
Currency::getRates(?string $rateType = null): array
```

```php
Currency::getRates();          // ['USD' => 41.5, 'EUR' => 47.1, ...]
Currency::getRates('all');     // ['USD' => ['buy' => 41.3, 'sell' => 41.7], ...]
```

All currencies the provider returned — not filtered by the `currencies` config. The base currency itself is not in the array.

## Checking support

```php
Currency::isSupported('JPY');            // the provider has a rate for JPY
Currency::getSupportedCurrencies();      // ['USD', 'EUR', ...]
Currency::getSupportedCurrenciesCount(); // e.g. ~200 for jsdelivr
```

> [!NOTE]
> These look at the provider's rates only, so the provider's base currency is not "supported": `isSupported('UAH')` is `false` with Monobank, even though `convert(100, 'USD', 'UAH')` works. Each call reads the rates (from cache, or from the API on a cache miss).

## Errors at a glance

| Situation | `convert()` / `convertAt()` | `getRate()` / `getRateAt()` | `getRates()` / `getRatesAt()` |
|---|---|---|---|
| Unknown rate type | `InvalidArgumentException` | `InvalidArgumentException` | `InvalidArgumentException` |
| No rate for the currency | `InvalidArgumentException` | `null` | currency absent |
| Custom base currency unknown to the provider | `InvalidArgumentException` | `null` | `InvalidArgumentException` |
| API down, nothing cached | `InvalidArgumentException` | `null` | `[]` (or the exception above with a custom base) |
| Provider without historical rates (`*At` methods) | `LogicException` | `LogicException` | `LogicException` |
