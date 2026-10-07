# Base currency

The base currency is what `getRate()` and `getRates()` are expressed in, and what `convert()` converts through. It is resolved in this order:

1. the runtime override from `Currency::setBaseCurrency()`
2. `default` in `config/currency.php` (`UAH`)
3. the provider's own base currency, if `default` is set to `null` (removing the key from the published config isn't enough — the package's own `'UAH'` is merged back in)

```php
Currency::getBaseCurrency(); // 'UAH'
```

## Overriding at runtime

```php
Currency::setBaseCurrency('USD');   // returns the Currency instance

Currency::getRate('EUR');   // 1.093… — USD per 1 EUR
Currency::getRate('UAH');   // 0.0232… — USD per 1 UAH
Currency::getRate('USD');   // 1.0
Currency::getRates();       // ['EUR' => 1.093…, 'PLN' => …, 'UAH' => 0.0232…] — no USD
```

(With Monobank rates of 43 UAH/USD and 47 UAH/EUR.)

The provider still returns rates against its own base (UAH for every built-in provider); `Currency` recalculates them through the new base:

| Rate type | Recalculated rate of X |
|---|---|
| `buy` | `X.buy / BASE.sell` |
| `sell` | `X.sell / BASE.buy` |
| `average` | `(X.buy + X.sell) / (BASE.buy + BASE.sell)` |

and adds the provider's base currency (UAH) to `getRates()` as `buy = 1 / BASE.sell`, `sell = 1 / BASE.buy`.

`convert()`, `convertAt()`, `getRateAt()` and `getRatesAt()` use the same recalculation. `convert()` between two currencies gives the same result whatever the base for `average`; for `buy`/`sell` the result differs slightly, because the spread of the base currency enters the formula.

## Requirements

The provider must have a rate for the new base currency:

- `getRates()` / `getRatesAt()` throw `InvalidArgumentException: Currency rate not found for: JPY`
- `convert()` throws the same exception for the leg that needs it
- `getRate()` returns `null`

`setBaseCurrency()` itself doesn't check anything — the error shows up on the next call. The same applies to `default` in config: with `default => 'EUR'` and a provider that doesn't return EUR, every call fails.

> [!NOTE]
> `setBaseCurrency()` changes the shared `Currency` instance for the rest of the request, job or command. Since 2.8.1 it is a scoped binding: Octane and queue workers start every request and job with a fresh one. Within one request, reset it with `Currency::setBaseCurrency(config('currency.default'))`, or use a separate `Currency` instance — see [Rate providers](providers.md#switching-provider).

## Base currency vs. formatting

The base currency has no effect on [`format()`](formatting.md), which only reads the `currencies` config of the currency you pass.
