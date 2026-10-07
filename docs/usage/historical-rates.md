# Historical rates

Rates as of a past date — to price an operation at the rate of the day it happened.

```php
use Fomvasss\Currency\Facades\Currency;
use Illuminate\Support\Carbon;

$date = Carbon::parse('2024-01-15');

Currency::convertAt(100, 'USD', 'UAH', $date);        // float
Currency::getRateAt('USD', $date);                    // ?float, config default rate type
Currency::getRateAt('USD', $date, 'buy');
Currency::getRatesAt($date);                          // ['USD' => 37.9, ...]
Currency::getRatesAt($date, 'all');                   // ['USD' => ['buy' => ..., 'sell' => ...], ...]

Currency::supportsHistoricalRates();                  // does the current provider support it
```

The date is a `DateTimeInterface` or a string, parsed with `Carbon::parse()` (since 2.8; before, only `currency_convert_at()` accepted a string):

```php
Currency::convertAt(100, 'USD', 'UAH', '2024-01-15');
currency_convert_at(100, 'USD', 'UAH', '2024-01-15');
```

Only `$date->format('Y-m-d')` is used — the calendar date in the object's own timezone; the time is ignored.

They behave like `convert()` / `getRate()` / `getRates()`, including rate types, rounding and the [base currency](base-currency.md) recalculation. When a rate is missing, the exception message includes the date: `Currency rate not found for: USD at 2024-01-15`.

## Provider support

| Alias | Historical | Notes |
|---|---|---|
| `nbu` | yes | On a weekend or holiday the API returns the rate of the previous working day |
| `privatbank` | yes | A different endpoint than current rates (`p24api/exchange_rates`); only currencies with the bank's own `purchaseRate`/`saleRate` — in practice USD and EUR, and not on every date. NBU rates from the same response are ignored |
| `jsdelivr` | yes | Dates from 2024-03-06; earlier dates return 404 → no rates |
| `exchangeratesapi` | yes | Same key / frankfurter limits as for current rates |
| `currencyapi` | yes | Requires a key |
| `fixer` | yes | Requires a key |
| `monobank` | **no** | No archive. The `*At` methods throw `LogicException: MonobankRateProvider does not support historical rates` |

Check support before calling when the provider can change at runtime:

```php
if (! Currency::supportsHistoricalRates()) {
    Currency::useProvider('nbu');
}
```

The `currency:convert` and `currency:rates` commands accept `--date=`, see [Artisan commands](../reference/commands.md).

## Caching

- Each date is cached under its own key: `currency_rates_{ProviderClass}_{generation}_{Y-m-d}`. The generation is a random string kept under `currency_rates_{ProviderClass}_historical_generation`.
- A successful result for a past date is cached **forever** by default (`cache_ttl_historical = null`, or an empty `CURRENCY_CACHE_TTL_HISTORICAL=`) — a past rate doesn't change. Set `CURRENCY_CACHE_TTL_HISTORICAL` (seconds) to expire it.
- Today's and future dates are cached for `cache_ttl`: the bank may not have published the rate yet, or the API answers with the previous day's one.
- An empty result (network error, error status, 404, or a response without rates) is cached for `cache_ttl_empty` (60 s).
- There is **no fallback cache**: a rate for another day is worse than no rate, so a failed fetch returns no rates and the conversion throws.
- `clearCache()` / `currency:rates --refresh` drop the historical keys too: they forget the generation, so every date is fetched again. The old per-date entries stay in the store until it evicts them.

## Bulk backfill

Fetch once per date, not once per operation:

```php
$byDate = $operations->groupBy(fn ($op) => $op->created_at->toDateString());

foreach ($byDate as $date => $ops) {
    $rates = Currency::getRatesAt(Carbon::parse($date), 'all');

    foreach ($ops as $op) {
        $rate = $rates[$op->currency] ?? null;  // ['buy' => ..., 'sell' => ...]
        // ...
    }
}
```

Repeated `getRatesAt()` calls for the same date are served from the cache anyway, but grouping avoids one cache read per operation.
