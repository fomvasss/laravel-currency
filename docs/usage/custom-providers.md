# Custom providers

A provider is any class implementing `Fomvasss\Currency\Contracts\RateProvider`. The easy way is to extend `AbstractRateProvider`, which brings HTTP fetching, caching, the fallback cache and the `CurrencyRateFetchFailed` event — you only describe the URL and the response format.

## Creating a provider

### Step 1: Create the provider class

```php
// app/Services/Currency/MyBankProvider.php
namespace App\Services\Currency;

use Fomvasss\Currency\RateProviders\AbstractRateProvider;

class MyBankProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';

    protected function getApiUrl(): string
    {
        return 'https://api.mybank.com/exchange-rates';
    }

    protected function parseResponse($response): array
    {
        $rates = [];
        foreach ($response['data'] ?? [] as $item) {
            $buy = (float) $item['buy_rate'];
            $sell = (float) $item['sell_rate'];

            if ($buy > 0 && $sell > 0) {
                $rates[strtoupper($item['currency'])] = [
                    'buy' => $buy,
                    'sell' => $sell,
                ];
            }
        }
        return $rates;
    }
}
```

What `parseResponse()` must return:

- keys — **upper-case** ISO codes (`getRate()` looks them up upper-cased)
- values — `['buy' => float, 'sell' => float]`: how many units of `$baseCurrency` one unit of the currency costs (`'USD' => ['buy' => 41.3, 'sell' => 41.7]` for a UAH base). If the API gives the inverse (1 UAH = 0.024 USD), store `1 / $value`
- no entry for the base currency itself, no zero rates (`convert()` divides by them)

`$response` is the decoded JSON array (`$response->json()`); a non-array body is treated as a failure before `parseResponse()` is called.

### Step 2: Register the provider in config

```php
// config/currency.php
'providers' => [
    // ...built-in providers
    'mybank' => \App\Services\Currency\MyBankProvider::class,
],

// Set as default (optional) — an alias or a class name
'default_provider' => 'mybank',
```

### Step 3: Use it

```php
Currency::useProvider('mybank');

// or, also accepting a class name or an instance
Currency::setRateProvider('mybank');
Currency::setRateProvider(\App\Services\Currency\MyBankProvider::class);
```

Providers are built by the service container, so constructor dependencies are injected.

## Adding historical rate support

To let `Currency::convertAt()`/`getRateAt()`/`getRatesAt()` work with your provider, implement `HistoricalRateProvider` and override the URL method; `getRatesAt()`/`getRateAt()` themselves are already implemented by `AbstractRateProvider`:

```php
use Fomvasss\Currency\Contracts\HistoricalRateProvider;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;

class MyBankProvider extends AbstractRateProvider implements HistoricalRateProvider
{
    // ...getApiUrl()/parseResponse() as above...

    protected function getHistoricalApiUrl(\DateTimeInterface $date): string
    {
        return 'https://api.mybank.com/exchange-rates?date=' . $date->format('Y-m-d');
    }

    protected function parseHistoricalResponse($response): array
    {
        // Override only if the historical endpoint's response shape differs from
        // parseResponse(); otherwise it defaults to parseResponse($response).
        return $this->parseResponse($response);
    }
}
```

Without `implements HistoricalRateProvider`, `Currency::supportsHistoricalRates()` returns `false` for your provider and the historical methods throw `LogicException` — same as `MonobankRateProvider`, which has no historical archive to query.

Historical rates never fall back to the last-known cache (a rate for another day is worse than no rate) and, by default, are cached forever per date — see [Historical rates](historical-rates.md#caching).

## Practical examples

### National Bank of Ukraine (NBU)

NBU provides a single rate per currency (no buy/sell split). Both fields are set to the same value. The package already ships this as the `nbu` provider (`NbuRateProvider`, with historical rates) — the example shows the pattern for any single-rate API.

```php
// app/Services/Currency/NbuProvider.php
namespace App\Services\Currency;

use Fomvasss\Currency\RateProviders\AbstractRateProvider;

class NbuProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';

    protected function getApiUrl(): string
    {
        return 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
    }

    protected function parseResponse($response): array
    {
        $rates = [];
        foreach ($response as $item) {
            $code = $item['cc'] ?? null;
            $rate = $item['rate'] ?? null;
            if ($code && $rate) {
                $rates[$code] = [
                    'buy' => (float) $rate,
                    'sell' => (float) $rate,
                ];
            }
        }
        return $rates;
    }
}
```

**Usage:**

```php
// config/currency.php
'default_provider' => \App\Services\Currency\NbuProvider::class,
```

### Provider with API key

If the key goes into the query string, just build it into `getApiUrl()` (that's what the built-in `currencyapi` and `fixer` do). For a header, override `fetchRatesFromApi()` — the HTTP part only. Caching (`fetchRates()`) stays in the parent; call `tryFallbackCache()` on failure to keep the fallback behaviour:

```php
// app/Services/Currency/SecureApiProvider.php
namespace App\Services\Currency;

use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SecureApiProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.currency_api.key');
    }

    protected function getApiUrl(): string
    {
        return 'https://api.example.com/v1/rates';
    }

    protected function parseResponse($response): array
    {
        $rates = [];
        if (!empty($response['success'])) {
            foreach ($response['rates'] as $currency => $data) {
                $rates[strtoupper($currency)] = [
                    'buy' => (float) $data['buy'],
                    'sell' => (float) $data['sell'],
                ];
            }
        }
        return $rates;
    }

    protected function fetchRatesFromApi(): array
    {
        $fallbackKey = $this->getCacheKey() . '_fallback';

        try {
            $response = Http::withToken($this->apiKey)->acceptJson()->timeout(10)->get($this->getApiUrl());

            if ($response->successful() && is_array($response->json())) {
                $rates = $this->parseResponse($response->json());

                if (!empty($rates)) {
                    Cache::put($fallbackKey, $rates, config('currency.cache_ttl_fallback', 86400));
                }

                return $rates;
            }

            event(new CurrencyRateFetchFailed(static::class, 'API returned error status: ' . $response->status()));
        } catch (\Throwable $e) {
            event(new CurrencyRateFetchFailed(static::class, $e->getMessage()));
        }

        return $this->tryFallbackCache($fallbackKey);
    }
}
```

**Configuration:**

```env
# .env
CURRENCY_API_KEY=your_api_key_here
```

```php
// config/services.php
'currency_api' => [
    'key' => env('CURRENCY_API_KEY'),
],
```

```php
// config/currency.php
'providers' => [
    'secure' => \App\Services\Currency\SecureApiProvider::class,
],
```

> [!WARNING]
> Don't override `fetchRates()` with `Cache::remember($key, $this->cacheTtl, ...)`: `$cacheTtl` is `null` unless set, and a `null` TTL makes `Cache::remember()` store the rates **forever**. Use `$this->getCacheTtl()` if you really need to replace `fetchRates()`.

### Multi-source provider with fallback

Tries each provider in order and returns the first non-empty result.

```php
// app/Services/Currency/MultiSourceProvider.php
namespace App\Services\Currency;

use Fomvasss\Currency\RateProviders\AbstractRateProvider;
use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Fomvasss\Currency\RateProviders\PrivatbankRateProvider;
use Illuminate\Support\Facades\Log;

class MultiSourceProvider extends AbstractRateProvider
{
    protected array $providers = [];

    public function __construct()
    {
        $this->providers = [
            new MonobankRateProvider(),
            new PrivatbankRateProvider(),
        ];
    }

    protected function getApiUrl(): string
    {
        return ''; // not used
    }

    protected function parseResponse($response): array
    {
        return []; // not used
    }

    public function getRates(): array
    {
        foreach ($this->providers as $provider) {
            try {
                $rates = $provider->getRates();
                if (!empty($rates)) {
                    return $rates;
                }
            } catch (\Exception $e) {
                Log::warning('Currency provider failed: ' . get_class($provider), [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return [];
    }

    public function clearCache(): void
    {
        foreach ($this->providers as $provider) {
            $provider->clearCache();
        }
    }
}
```

Notes:

- All inner providers must have the same base currency as the wrapper (`UAH` here).
- Each inner provider has its own cache and fallback cache, so "non-empty" may already mean "fallback rates from yesterday" — the next provider is tried only when the first has nothing at all.
- The inner providers return different currency sets (PrivatBank has only USD/EUR), so the set of supported currencies depends on which one answered.

## Provider configuration

### Custom cache TTL

```php
class MyBankProvider extends AbstractRateProvider
{
    protected ?int $cacheTtl = 600; // 10 minutes instead of config('currency.cache_ttl')
    // ...existing code...
}
```

The property must be declared `?int`, as in the parent class — `protected int $cacheTtl` is a fatal error. At runtime: `$provider->setCacheTtl(600)`.

### Custom fallback rates

Override `getFallbackRates()` to return static rates when the API is unavailable and the fallback cache is empty:

```php
class SafeProvider extends AbstractRateProvider
{
    protected function getFallbackRates(): array
    {
        return json_decode(
            file_get_contents(storage_path('fallback_rates.json')),
            true
        ) ?? [];
    }
    // ...existing code...
}
```

Static rates are cached under the main key for `cache_ttl_empty`, like fallback ones, so the API is retried soon — see [Caching & failures](caching.md).

### Other extension points

| Method | Default | Override to |
|---|---|---|
| `getCacheKey()` | `currency_rates_{full class name, `\` → `_`}` (built-in providers: `currency_rates_{class basename}`) | separate caches of two instances with different settings (e.g. different base currency) |
| `getHistoricalCacheKey($date)` | `{cache key}_{generation}_{Y-m-d}` | change the per-date key |
| `getBaseCurrency()` | `$baseCurrency` | compute the base dynamically |

## Mock provider for testing

Implement the `RateProvider` contract directly for full control in tests:

```php
// tests/Fakes/MockProvider.php
namespace Tests\Fakes;

use Fomvasss\Currency\Contracts\RateProvider;

class MockProvider implements RateProvider
{
    protected array $mockRates = [
        'USD' => ['buy' => 40.00, 'sell' => 41.00],
        'EUR' => ['buy' => 43.00, 'sell' => 44.00],
        'GBP' => ['buy' => 50.00, 'sell' => 51.00],
    ];

    public function getRates(): array
    {
        return $this->mockRates;
    }

    public function getRate(string $currency): ?array
    {
        return $this->mockRates[strtoupper($currency)] ?? null;
    }

    public function supports(string $currency): bool
    {
        return isset($this->mockRates[strtoupper($currency)]);
    }

    public function getBaseCurrency(): string
    {
        return 'UAH';
    }

    public function getSupportedCurrencies(): array
    {
        return array_keys($this->mockRates);
    }

    public function getSupportedCurrenciesCount(): int
    {
        return count($this->mockRates);
    }

    public function clearCache(): void {}

    public function setMockRates(array $rates): void
    {
        $this->mockRates = $rates;
    }
}
```

**Usage in tests:**

```php
use Fomvasss\Currency\Facades\Currency;
use Tests\Fakes\MockProvider;

Currency::setRateProvider(new MockProvider());

$rate = Currency::getRate('USD');             // 40.5 (average of 40.00 and 41.00)
$uah = Currency::convert(100, 'USD', 'UAH');  // 4050.0
```

The app's `currency.default` must equal the mock's base currency (`UAH`), otherwise rates are recalculated through `default`. For the `*At` methods, implement `HistoricalRateProvider` (`getRatesAt()`, `getRateAt()`) as well.
