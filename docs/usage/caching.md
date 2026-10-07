# Caching & failures

Rates are always read through Laravel's default cache store; a provider calls its API only on a cache miss.

## Current rates

On `getRates()` (and everything built on it — `convert()`, `getRate()`, `isSupported()`, …):

1. Read `currency_rates_{ProviderClass}` from the cache. Found → return it. `{ProviderClass}` is the short class name for built-in providers (`NbuRateProvider`) and the full name with `_` instead of `\` for any other (`App_Currency_NbuRateProvider`).
2. Otherwise call the API (`Http::timeout(10)`).
3. **Success** with rates → store them in the cache for `cache_ttl` (1 h) and in the fallback key `currency_rates_{ProviderClass}_fallback` for `cache_ttl_fallback` (1 day).
4. **Failure** (exception, non-2xx status, non-JSON body) → use the fallback key if it has rates, otherwise the provider's static `getFallbackRates()` (empty for every built-in provider).
5. Whatever came out of 3–4 is stored under the main key: fetched rates for `cache_ttl`; fallback, static or empty rates for `cache_ttl_empty` (60 s).

Consequences:

- When the API is down but the fallback cache is warm, rates up to `cache_ttl_fallback` old are served. The API is retried every `cache_ttl_empty`, so fresh rates come back within a minute of its recovery. Before 2.7.3 the fallback rates were cached for the full `cache_ttl`.
- When nothing is cached at all, rates are empty: `convert()` throws, `getRate()` returns `null`. The next API attempt happens after `cache_ttl_empty`, so a burst of requests doesn't hammer a dead API.
- A 2xx response that parses to no rates is not treated as a failure: no event, no fallback cache — the empty result is cached for `cache_ttl_empty`. Fixer, for example, reports errors (invalid key, plan restrictions) as HTTP 200 with `"success": false`.

`cache_ttl` can also be overridden per provider instance:

```php
Currency::getProvider()->setCacheTtl(600); // methods of AbstractRateProvider
```

The provider instance does not memoize rates — every call goes to the cache, so long-running workers (Octane, Horizon) pick up new rates as soon as the cache entry expires.

> [!NOTE]
> The cache key is built from the class **basename**. Two providers with the same short class name (e.g. your own `App\Rates\NbuRateProvider` and the built-in one) share the same cache entries.

## Historical rates

Cached per date, forever by default, without fallback — see [Historical rates](historical-rates.md#caching).

## Clearing the cache

```php
use Fomvasss\Currency\Facades\CurrencyProvider;

Currency::clearCache();                         // current provider: main, fallback and per-date keys
CurrencyProvider::provider('nbu')->clearCache(); // a specific provider
```

```bash
php artisan currency:rates --refresh
php artisan currency:rates --provider=nbu --refresh
```

`clearCache()` removes the fallback rates as well — if the API is down right after, there is nothing to fall back to. Historical per-date rates are dropped too.

## The CurrencyRateFetchFailed event

`Fomvasss\Currency\Events\CurrencyRateFetchFailed` is dispatched when a fetch fails — use it for alerts.

| Property | Type | Description |
|---|---|---|
| `providerClass` | `string` | FQCN of the provider |
| `errorMessage` | `string` | What happened, see below |
| `usingFallback` | `bool` | `true` for the "serving fallback" event |
| `fallbackRates` | `?array` | The rates being served instead (may be `[]`) |
| `date` | `?DateTimeInterface` | The date for a historical fetch, otherwise `null` |

A failed **current-rate** fetch dispatches **two** events:

1. `usingFallback = false`, `errorMessage` = the exception message, `API returned error status: 503` or `API returned a non-array response`;
2. `usingFallback = true`, with either `Using fallback cached rates` and the cached rates, or `No cached rates available, using static fallback` and the static rates — `[]` for every built-in provider, i.e. **nothing is actually served**.

A failed **historical** fetch dispatches one event with `usingFallback = false` and `date` set.

```php
use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(function (CurrencyRateFetchFailed $event) {
    if ($event->usingFallback && empty($event->fallbackRates)) {
        Log::critical('No currency rates available', ['provider' => $event->providerClass]);
    }
});
```

Because the main key caches the failure result, the events fire once per `cache_ttl_empty` while the API is failing — not on every call.

The package also logs: `Log::error` on an exception and when no fallback rates exist, `Log::warning` when serving fallback rates and on any historical failure.

## Falling back to another provider

A failing provider doesn't throw, it returns fewer (or no) rates, so `try/catch` around `getRate()` doesn't detect it. Check the result instead:

```php
use Fomvasss\Currency\Facades\CurrencyProvider;

$rate = Currency::getRate('USD');

if ($rate === null) {
    $rate = CurrencyProvider::provider('jsdelivr')->getRate('USD');   // raw ['buy' => .., 'sell' => ..]
}
```

or wrap several providers into one — [multi-source provider](custom-providers.md#multi-source-provider-with-fallback).

## Testing

Fake the HTTP layer and use the array cache store (Testbench and Laravel's default `phpunit.xml` already do):

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.monobank.ua/*' => Http::response([
        ['currencyCodeA' => 840, 'currencyCodeB' => 980, 'rateBuy' => 41.3, 'rateSell' => 41.7],
    ]),
]);

Currency::convert(100, 'USD', 'UAH'); // 4150.0
```

Or swap the provider for a fixed one — [mock provider](custom-providers.md#mock-provider-for-testing). With a persistent cache store, call `Currency::clearCache()` between tests.
