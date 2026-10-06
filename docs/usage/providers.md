# Rate providers

A provider fetches rates from one API and returns them against its own base currency. The `Currency` facade uses one provider at a time: `default_provider` from config, or whatever you switched to.

## Built-in providers

| Alias | Source | API key | Currencies | Buy/sell | Historical |
|---|---|---|---|---|---|
| `monobank` | Monobank public API | no | 15 against UAH | real | no |
| `privatbank` | PrivatBank public API | no | EUR, USD | real | yes |
| `nbu` | National Bank of Ukraine | no | all NBU official rates | equal (official rate) | yes |
| `jsdelivr` | `@fawazahmed0/currency-api` via jsDelivr CDN | no | 150+ | synthetic ±0.5% | yes, from 2024-03-06 |
| `exchangeratesapi` | exchangeratesapi.io, or frankfurter.dev without a key | optional | ECB list without a key | equal | yes |
| `currencyapi` | currencyapi.com | required | per API | equal | yes |
| `fixer` | fixer.io | required | per API | equal | yes |

Base currency of every built-in provider is `UAH`. Per-provider details, URLs and limitations — [Providers reference](../reference/providers.md).

> [!WARNING]
> **`exchangeratesapi`, `currencyapi` and `fixer` return rates in the opposite direction** to what `Currency` expects. These APIs answer "how much of the currency for 1 unit of the base" (1 UAH = 0.024 USD), and the providers store that number as is, while `Currency` treats a rate as "how much of the base for 1 unit of the currency" (1 USD = 41.5 UAH). With these providers `convert(100, 'USD', 'UAH')` returns `2.4` instead of `4150`, and `getRate()` returns the inverted rate. `nbu`, `monobank`, `privatbank` and `jsdelivr` are not affected (jsDelivr inverts the API value itself). Until this is fixed in the package, use the affected providers through a subclass that inverts the rates — see [Custom providers](custom-providers.md#inverting-a-providers-rates).

> [!NOTE]
> `exchangeratesapi` without a key uses frankfurter.dev, which only knows ECB currencies — `UAH` is not one of them, and the provider's base is `UAH`, so every request fails. Set `EXCHANGE_RATES_API_KEY`, or rebind the provider with an ECB base currency (below). Fixer's free plan allows only HTTP and `base=EUR`; the provider always calls `https://data.fixer.io` with `base=UAH`, so a free key gets an access error.

## Switching provider

```php
Currency::useProvider('nbu');                    // alias from config('currency.providers')
Currency::setRateProvider('nbu');                // alias,
Currency::setRateProvider(NbuRateProvider::class); // class name,
Currency::setRateProvider(new NbuRateProvider()); // or an instance

Currency::useProvider('nbu')->getRate('USD');    // both return the Currency instance
```

`useProvider()` accepts only configured aliases (`InvalidArgumentException: Provider 'x' is not configured`); `setRateProvider()` also takes a class name or an instance. A class that doesn't implement `RateProvider` throws `InvalidArgumentException`.

```php
Currency::getProvider();            // current RateProvider instance (alias: getRateProvider())
Currency::getAvailableProviders();  // config('currency.providers')
```

> [!WARNING]
> `Currency` is a singleton. `useProvider()`, `setRateProvider()` and `setBaseCurrency()` change it for the rest of the process — every later call in the same request, job or command sees the switched provider. Under Octane, or in a queue worker, that is **every later request or job handled by the same worker**. Switch back when you're done, or use a separate instance:
>
> ```php
> use Fomvasss\Currency\Currency;
>
> $nbu = new Currency(app('currency.manager')->resolve('nbu'), config('currency'));
> $nbu->convert(100, 'USD', 'UAH');
> ```

## Using a provider directly

The `CurrencyProvider` facade (`currency.manager`, a `ProviderManager`) builds provider instances without touching the `Currency` singleton. It is not auto-aliased — import it by class name:

```php
use Fomvasss\Currency\Facades\CurrencyProvider;

$nbu = CurrencyProvider::provider('nbu');   // new NbuRateProvider instance
$nbu->getRates();                           // raw ['USD' => ['buy' => .., 'sell' => ..], ...] against UAH
$nbu->getRate('USD');                       // ['buy' => .., 'sell' => ..] or null

CurrencyProvider::provider();               // default_provider (alias only, not a class name)
CurrencyProvider::getRates();               // unknown methods are forwarded to a new default provider
```

Raw provider rates are not recalculated to your `default` base currency and not reduced by rate type. See [Contracts, events & manager](../reference/contracts.md).

## Constructor arguments

Providers are built through the service container (`$container->make($class)`). `ExchangeRatesApiProvider`, `CurrencyApiProvider` and `FixerProvider` accept `(?string $apiKey = null, string $baseCurrency = 'UAH')`; the API key falls back to config. To change the base currency, bind the class in a service provider:

```php
use Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider;

public function register(): void
{
    $this->app->bind(ExchangeRatesApiProvider::class, fn () => new ExchangeRatesApiProvider(null, 'EUR'));
}
```

Set `default` in `config/currency.php` to the same currency (`'EUR'`): when the two differ, `Currency` needs the provider to return a rate for your `default` currency to recalculate, and frankfurter has none for `UAH`.

`monobank`, `privatbank`, `nbu` and `jsdelivr` have no constructor; their base currency is the `$baseCurrency` property (`UAH`), which a subclass can override — but only if the API really returns rates against that currency.

## Choosing a provider

- **Ukrainian shop, bank rates** — `monobank` (buy/sell, many currencies) or `privatbank` (USD/EUR only)
- **Official rate, accounting, historical data** — `nbu`
- **Many currencies, no key** — `jsdelivr` (daily mid rates; the spread is synthetic)
- Fallback between providers isn't built in — when the API fails, the provider serves its own [fallback cache](caching.md). For a chain of providers, write a [multi-source provider](custom-providers.md#multi-source-provider-with-fallback).
