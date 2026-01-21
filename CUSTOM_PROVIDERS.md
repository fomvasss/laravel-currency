# Власні провайдери курсів валют
Посібник по створенню та підключенню власних провайдерів курсів валют.
## Створення провайдера
### Крок 1: Створіть клас провайдера
```php
// app/Services/Currency/MyBankProvider.php
namespace App\Services\Currency;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;
class MyBankProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';
    protected function getApiUrl(): string
    {
        return 'https://api.mybank.com/exchange-rates'\;
    }
    protected function parseResponse($response): array
    {
        $rates = [];
        foreach ($response['data'] as $item) {
            $rates[$item['currency']] = [
                'buy' => (float) $item['buy_rate'],
                'sell' => (float) $item['sell_rate'],
            ];
        }
        return $rates;
    }
}
```
### Крок 2: Вкажіть провайдер у конфігу
```php
// config/currency.php
return [
    'default_provider' => \App\Services\Currency\MyBankProvider::class,
    // ... інші налаштування
];
```
Готово! Тепер ваш провайдер буде використовуватися за замовчуванням.
## Практичні приклади
### NBU (Національний банк України)
```php
// app/Services/Currency/NbuProvider.php
namespace App\Services\Currency;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;
class NbuProvider extends AbstractRateProvider
{
    protected string $baseCurrency = 'UAH';
    protected function getApiUrl(): string
    {
        return 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json'\;
    }
    protected function parseResponse($response): array
    {
        $rates = [];
        foreach ($response as $item) {
            $currencyCode = $item['cc'] ?? null;
            $rate = $item['rate'] ?? null;
            if ($currencyCode && $rate) {
                // НБУ надає тільки один курс
                $rates[$currencyCode] = [
                    'buy' => (float) $rate,
                    'sell' => (float) $rate,
                ];
            }
        }
        return $rates;
    }
}
```
**Використання:**
```php
// config/currency.php
'default_provider' => \App\Services\Currency\NbuProvider::class,
```
### Провайдер з API ключем
```php
// app/Services/Currency/SecureApiProvider.php
namespace App\Services\Currency;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
class SecureApiProvider extends AbstractRateProvider
{
    protected string $apiKey;
    protected string $baseCurrency = 'UAH';
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.currency_api.key');
    }
    protected function getApiUrl(): string
    {
        return 'https://api.example.com/v1/rates'\;
    }
    protected function parseResponse($response): array
    {
        $rates = [];
        if (isset($response['success']) && $response['success']) {
            foreach ($response['rates'] as $currency => $data) {
                $rates[$currency] = [
                    'buy' => (float) $data['buy'],
                    'sell' => (float) $data['sell'],
                ];
            }
        }
        return $rates;
    }
    protected function fetchRates(): array
    {
        $cacheKey = $this->getCacheKey();
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])->timeout(10)->get($this->getApiUrl());
                if ($response->successful()) {
                    return $this->parseResponse($response->json());
                }
                return $this->getFallbackRates();
            } catch (\Exception $e) {
                \Log::error('Currency API error: ' . $e->getMessage());
                return $this->getFallbackRates();
            }
        });
    }
}
```
**Налаштування:**
```env
# .env
CURRENCY_API_KEY=your_api_key_here
```
```php
// config/services.php
'currency_api' => [
    'key' => env('CURRENCY_API_KEY'),
],
// config/currency.php
'default_provider' => \App\Services\Currency\SecureApiProvider::class,
```
### Мультипровайдер з fallback
```php
// app/Services/Currency/MultiSourceProvider.php
namespace App\Services\Currency;
use Fomvasss\Currency\RateProviders\AbstractRateProvider;
use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Fomvasss\Currency\RateProviders\PrivatbankRateProvider;
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
        return ''; // Не використовується
    }
    protected function parseResponse($response): array
    {
        return []; // Не використовується
    }
    public function getRates(): array
    {
        foreach ($this->providers as $provider) {
            try {
                $rates = $provider->getRates();
                if (!empty($rates)) {
                    \Log::info('Використано провайдер: ' . get_class($provider));
                    return $rates;
                }
            } catch (\Exception $e) {
                \Log::warning('Провайдер не працює: ' . get_class($provider), [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        return [];
    }
}
```
## Налаштування провайдера
### Зміна TTL кешу
```php
class MyBankProvider extends AbstractRateProvider
{
    protected int $cacheTtl = 600; // 10 хвилин замість 1 години
    // ...existing code...
}
```
### Власна логіка fallback
```php
class SafeProvider extends AbstractRateProvider
{
    protected function getFallbackRates(): array
    {
        // Завантажити статичні курси з файлу
        return json_decode(
            file_get_contents(storage_path('fallback_rates.json')),
            true
        );
    }
    // ...existing code...
}
```
## Mock провайдер для тестування
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
    public function setMockRates(array $rates): void
    {
        $this->mockRates = $rates;
    }
}
```
**Використання:**
```php
// config/currency.php
'default_provider' => \App\Services\Currency\MockProvider::class,
```
