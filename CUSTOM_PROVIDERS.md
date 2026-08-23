# Custom Rate Providers

A guide on creating and registering custom currency rate providers.

## Creating a Provider

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
        foreach ($response['data'] as $item) {
            $rates[$item['currency']] = [
                'buy'  => (float) $item['buy_rate'],
                'sell' => (float) $item['sell_rate'],
            ];
        }
        return $rates;
    }
}
```

### Step 2: Register the provider in config

```php
// config/currency.php
'providers' => [
    'mybank' => \App\Services\Currency\MyBankProvider::class,
],

// Set as default (optional)
'default_provider' => 'mybank',
```

### Step 3: Use it

```php
Currency::useProvider('mybank');

// or switch at runtime
Currency::setRateProvider('mybank');
```

---

## Adding Historical Rate Support

To let `Currency::convertAt()`/`getRateAt()`/`getRatesAt()` work with your provider, implement
`HistoricalRateProvider` and override the two methods it needs:

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

Without `implements HistoricalRateProvider`, `Currency::supportsHistoricalRates()` returns
`false` for your provider and the historical methods throw `LogicException` — same as
`MonobankRateProvider`, which has no historical archive to query.

Historical rates never fall back to the last-known cache (a rate for another day is worse than
no rate) and, by default, are cached forever per date — see the "Historical Rates" section in
the README.

---

## Practical Examples

### National Bank of Ukraine (NBU)

NBU provides a single rate per currency (no buy/sell split). Both fields are set to the same value.

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
                    'buy'  => (float) $rate,
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

---

### Provider with API Key

For APIs that require authentication, override `fetchRates()` to add custom headers.

```php
// app/Services/Currency/SecureApiProvider.php
namespace App\Services\Currency;

use Fomvasss\Currency\RateProviders\AbstractRateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SecureApiProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';
    protected string $apiKey;

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
                $rates[$currency] = [
                    'buy'  => (float) $data['buy'],
                    'sell' => (float) $data['sell'],
                ];
            }
        }
        return $rates;
    }

    protected function fetchRates(): array
    {
        return Cache::remember($this->getCacheKey(), $this->cacheTtl, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ])->timeout(10)->get($this->getApiUrl());

                if ($response->successful()) {
                    return $this->parseResponse($response->json());
                }

                return $this->getFallbackRates();
            } catch (\Exception $e) {
                Log::error('Currency API error: ' . $e->getMessage());
                return $this->getFallbackRates();
            }
        });
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

---

### Multi-source Provider with Fallback

Tries each provider in order and returns the first successful result.

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
                    Log::info('Currency provider used: ' . get_class($provider));
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
}
```

---

## Provider Configuration

### Custom cache TTL

```php
class MyBankProvider extends AbstractRateProvider
{
    protected int $cacheTtl = 600; // 10 minutes instead of the default 1 hour
    // ...existing code...
}
```

### Custom fallback rates

Override `getFallbackRates()` to return static rates when the API is unavailable:

```php
class SafeProvider extends AbstractRateProvider
{
    protected function getFallbackRates(): array
    {
        return json_decode(
            file_get_contents(storage_path('fallback_rates.json')),
            true
        );
    }
    // ...existing code...
}
```

---

## Mock Provider for Testing

Implement the `RateProvider` contract directly for full control in tests:

```php
// app/Services/Currency/MockProvider.php
namespace App\Services\Currency;

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
use App\Services\Currency\MockProvider;
use Fomvasss\Currency\Facades\Currency;

Currency::setRateProvider(new MockProvider());

$rate = Currency::getRate('USD'); // 40.5 (average of 40.00 and 41.00)
```
