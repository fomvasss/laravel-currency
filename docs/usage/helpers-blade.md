# Helpers & Blade

## Helpers

Global functions, autoloaded with the package. Each one calls the `currency` singleton, so they follow the current provider and base currency.

```php
currency_convert(100, 'USD', 'EUR');                 // Currency::convert()
currency_convert(100, 'USD', 'EUR', 'sell');

currency_convert_at(100, 'USD', 'UAH', '2024-01-15'); // Currency::convertAt(); a string or DateTimeInterface
currency_convert_at(100, 'USD', 'UAH', now()->subMonth());

currency_format(1234.56, 'USD');                     // "$ 1,234.56"
currency_format(1234.56, 'USD', false);              // "1,234.56"

currency_rate('USD');                                // Currency::getRate(), ?float
currency_rate('USD', 'buy');

currency_symbol('USD');                              // '$'; the code itself if not configured
```

Signatures — [Currency facade reference](../reference/currency.md#helpers). Every helper is declared only if a function with that name doesn't exist yet. There are no helpers for `getRateAt()` / `getRatesAt()`.

## Blade directives

```blade
@currency(100, 'USD', 'EUR')            {{-- convert(), prints the float: 92.47 --}}
@currency($price, 'UAH', 'USD', 'sell')

@currencyFormat(1234.56, 'USD')          {{-- format(): $ 1,234.56 --}}
@currencyFormat($price, 'USD', false)

@currencyRate('USD')                     {{-- getRate(): 41.5 --}}
@currencyRate('USD', 'buy')

@currencySymbol('USD')                   {{-- $ --}}
```

The arguments are passed to the method as is, so every optional argument of the method is accepted.

> [!NOTE]
> Directives print with `echo`, **without** escaping, and are compiled into the cached views. `@currency` prints a raw float (`92.47`, or `92.5` — trailing zeros are dropped); use `@currencyFormat` with a converted amount for display. `@currencyRate` prints nothing when the rate is `null`, and `@currency` throws when a rate is missing, failing the whole view.

For full control use the facade in Blade:

```blade
{{ Currency::format(Currency::convert($product->price, 'UAH', $userCurrency), $userCurrency) }}
```
