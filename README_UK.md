# Laravel Currency

[![Laravel](https://img.shields.io/badge/Laravel-9+-red)](https://laravel.com/)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-currency.svg?style=for-the)](https://github.com/fomvasss/laravel-currency)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-currency.svg)](https://packagist.org/packages/fomvasss/laravel-currency)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Підтримка

Якщо цей пакет є корисним для вас, розгляньте можливість підтримки його розробки:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> Адреса USDT TRC20: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`

Laravel-пакет для конвертації валют та управління курсами з підтримкою кількох провайдерів.

## Можливості

- Конвертація валют за курсами купівлі / продажу / середнім
- Сім вбудованих провайдерів курсів (Monobank, ПриватБанк, НБУ, jsDelivr, ExchangeRatesAPI, CurrencyAPI, Fixer)
- Автоматичне кешування курсів із налаштовуваним TTL та резервним кешем
- Динамічна зміна базової валюти з автоматичним перерахунком курсів
- Форматування суми з символом валюти, точністю та локальними роздільниками
- Глобальні PHP-хелпери та Blade-директиви для використання у шаблонах
- Підтримка власних провайдерів через простий інтерфейс

## Вимоги

- PHP 8.1+
- Laravel 9.x, 10.x, 11.x, 12.x або 13.x

## Встановлення

```bash
composer require fomvasss/laravel-currency
```

Публікація конфігурації:

```bash
php artisan vendor:publish --tag=currency-config
```

## Конфігурація

Після публікації файл розташований у `config/currency.php`.

### Доступні параметри

```php
return [
    // Базова валюта за замовчуванням
    'default' => 'UAH',

    // Провайдер курсів за замовчуванням (ключ з масиву 'providers' або повна назва класу).
    // Невідомий псевдонім/клас кидає InvalidArgumentException під час завантаження.
    'default_provider' => env('CURRENCY_DEFAULT_PROVIDER', 'monobank'),

    // Доступні провайдери
    'providers' => [
        'nbu'              => \Fomvasss\Currency\RateProviders\NbuRateProvider::class,
        'monobank'         => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class,
        'privatbank'       => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class,
        'jsdelivr'         => \Fomvasss\Currency\RateProviders\JsDelivrProvider::class,
        'exchangeratesapi' => \Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider::class,
        'currencyapi'      => \Fomvasss\Currency\RateProviders\CurrencyApiProvider::class,
        'fixer'            => \Fomvasss\Currency\RateProviders\FixerProvider::class,
    ],

    // TTL основного кешу курсів у секундах (за замовчуванням: 1 година)
    'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600),

    // TTL резервного кешу у секундах — використовується, коли API недоступний (за замовчуванням: 1 день)
    'cache_ttl_fallback' => env('CURRENCY_CACHE_TTL_FALLBACK', 86400),

    // Інтервал повтору в секундах при порожньому результаті — API недоступний і fallback порожній (за замовчуванням: 60)
    'cache_ttl_empty' => env('CURRENCY_CACHE_TTL_EMPTY', 60),

    // Тип курсу за замовчуванням: 'buy', 'sell' або 'average'
    'default_rate_type' => env('CURRENCY_DEFAULT_RATE_TYPE', 'average'),

    // Кількість десяткових знаків за замовчуванням (може бути перевизначена для кожної валюти)
    'default_precision' => env('CURRENCY_DEFAULT_PRECISION', 2),

    // API-ключі для платних провайдерів
    'exchange_rates_api_key' => env('EXCHANGE_RATES_API_KEY', null),
    'currencyapi_key'        => env('CURRENCYAPI_KEY', null),
    'fixer_api_key'          => env('FIXER_API_KEY', null),

    // Активні валюти з параметрами форматування
    'currencies' => [
        'UAH' => [
            'code'              => 'UAH',
            'title'             => 'Ukraine, Hryvnia',
            'symbol'            => '₴',
            'precision'         => 2,
            'thousandSeparator' => ' ',
            'decimalSeparator'  => ',',
            'symbolPlacement'   => 'after',
        ],
        'USD' => [
            'code'              => 'USD',
            'title'             => 'US Dollar',
            'symbol'            => '$',
            'precision'         => 2,
            'thousandSeparator' => ',',
            'decimalSeparator'  => '.',
            'symbolPlacement'   => 'before',
        ],
        // ... інші валюти (розкоментуйте потрібні у config/currency.php)
    ],
];
```

### Змінні середовища (.env)

```env
CURRENCY_DEFAULT_PROVIDER=monobank
CURRENCY_DEFAULT_RATE_TYPE=average
CURRENCY_CACHE_TTL=3600
CURRENCY_CACHE_TTL_FALLBACK=86400
CURRENCY_DEFAULT_PRECISION=2

# Для платних провайдерів (за потреби)
EXCHANGE_RATES_API_KEY=your_key
CURRENCYAPI_KEY=your_key
FIXER_API_KEY=your_key
```

## Провайдери курсів

| Псевдонім        | Клас                         | Валюти         | API-ключ | Примітки                             |
|------------------|------------------------------|----------------|----------|--------------------------------------|
| `nbu`            | `NbuRateProvider`            | 30+            | Ні       | Національний банк України            |
| `monobank`       | `MonobankRateProvider`       | Декілька       | Ні       | API Монобанку                        |
| `privatbank`     | `PrivatbankRateProvider`     | EUR, USD       | Ні       | Обмеження API ПриватБанку            |
| `jsdelivr`       | `JsDelivrProvider`           | 150+           | Ні       | Безкоштовний CDN, оновлення щодня. Купівля/продаж — синтетичні: спред ±0.5% (`$spread`) навколо ринкового курсу, перевизначається в нащадку. Без вбудованого статичного fallback: якщо CDN недоступний, `getRates()` повертає порожній масив (якщо немає резервного кешу) |
| `exchangeratesapi` | `ExchangeRatesApiProvider` | Декілька       | Опційно  | https://exchangeratesapi.io. **Без API-ключа** використовується [frankfurter.dev](https://frankfurter.dev) (тільки валюти ECB) — `UAH` не є валютою ECB і не може бути `baseCurrency` в цьому режимі (`new ExchangeRatesApiProvider(null, 'UAH')` поверне 404); використовуйте `new ExchangeRatesApiProvider(null, 'EUR')` або задайте `exchange_rates_api_key` |
| `currencyapi`    | `CurrencyApiProvider`        | Декілька       | Так      | https://currencyapi.com (300 запитів/місяць безкоштовно) |
| `fixer`          | `FixerProvider`              | Декілька       | Так      | https://fixer.io. **Безкоштовний тариф працює лише по HTTP і `base` фіксований на EUR** — цей провайдер завжди звертається по HTTPS, тому безкоштовний ключ поверне помилку доступу до базової валюти; для HTTPS та/або іншої базової валюти потрібен платний тариф |

> **Octane / Horizon / довгоживучі воркери:** курси завжди читаються через `Cache` (ніколи не мемоізуються на інстансі провайдера), тому довгоживучий воркер отримує свіжі курси одразу після закінчення TTL кешу — без витоку стану між запитами чи тенантами.

## Базове використання

```php
use Fomvasss\Currency\Facades\Currency;

// Конвертація валют
$euros = Currency::convert(100, 'USD', 'EUR');

// Отримання курсу
$rate     = Currency::getRate('USD');           // null -> config('currency.default_rate_type'), за замовчуванням 'average'
$buyRate  = Currency::getRate('USD', 'buy');    // купівля
$sellRate = Currency::getRate('USD', 'sell');   // продаж

// Отримання всіх курсів
$rates    = Currency::getRates();               // null -> тип курсу з конфігу
$rates    = Currency::getRates('all');          // ['USD' => ['buy' => ..., 'sell' => ...], ...]

// Форматування валюти
$formatted = Currency::format(1234.56, 'USD');         // $ 1,234.56
$noSymbol  = Currency::format(1234.56, 'USD', false);  // 1,234.56
```

`$rateType` (у `convert()`, `getRate()`, `getRates()` та хелпері `currency_rate()`) приймає лише `'buy'`, `'sell'`, `'average'` (і `'all'` для `getRates()`) — інше значення кидає `InvalidArgumentException`.

## Перемикання провайдерів

```php
// За псевдонімом (рекомендований спосіб)
Currency::useProvider('nbu');
Currency::useProvider('monobank');
Currency::useProvider('jsdelivr');

// Через setRateProvider — приймає псевдонім, назву класу або екземпляр
Currency::setRateProvider('nbu');
Currency::setRateProvider(\Fomvasss\Currency\RateProviders\NbuRateProvider::class);
Currency::setRateProvider(new NbuRateProvider());

$rate = Currency::getRate('USD');
```

## Базова валюта

```php
// Отримати поточну базову валюту
$base = Currency::getBaseCurrency(); // 'UAH' (з конфігу)

// Змінити базову валюту під час виконання
// Усі курси, що повертаються getRate() та getRates(), автоматично перераховуються
Currency::setBaseCurrency('USD');

$rates = Currency::getRates();
// Повертає курси відносно USD: ['EUR' => 0.92, 'UAH' => 41.5, ...]
// USD відсутній у масиві (це база, курс = 1.0)

// Конвертація після зміни бази
$amount = Currency::convert(100, 'EUR', 'GBP');
```

`setBaseCurrency()` вимагає, щоб поточний провайдер мав курс для цієї валюти — інакше `getRates()`/`getRate()`/`convert()` кидають `InvalidArgumentException` (наприклад, `setBaseCurrency('JPY')`, коли активний провайдер не підтримує JPY).

## Перевірка можливостей провайдера

```php
$currencies = Currency::getSupportedCurrencies();      // ['USD', 'EUR', ...]
$count      = Currency::getSupportedCurrenciesCount(); // наприклад, 150 для jsdelivr

if (Currency::isSupported('JPY')) {
    $rate = Currency::getRate('JPY');
}
```

## Активні валюти (з конфігурації)

```php
$currencies = Currency::getActiveCurrencies();     // повний масив конфігурації
$codes      = Currency::getActiveCurrencyCodes();  // ['UAH', 'USD', 'EUR', ...]
$allConfig  = Currency::getAllCurrencies();         // псевдонім getActiveCurrencies()

$usdConfig  = Currency::getCurrencyConfig('USD');  // ['symbol' => '$', 'precision' => 2, ...]
$precision  = Currency::getPrecision('USD');        // 2
```

## Управління кешем

```php
// Очистити кеш курсів поточного провайдера
Currency::clearCache();
```

Резервний кеш зберігає останні успішно отримані курси та використовується автоматично, коли основний API недоступний. TTL налаштовується через параметр `cache_ttl_fallback` або змінну середовища `CURRENCY_CACHE_TTL_FALLBACK`.

## Хелпер-функції

Глобальні функції доступні без підключення будь-яких класів:

```php
// Конвертація
$result = currency_convert(100, 'USD', 'EUR');
$result = currency_convert(100, 'USD', 'EUR', 'sell');

// Форматування із символом
$output = currency_format(1234.56, 'USD');          // $ 1,234.56
$output = currency_format(1234.56, 'USD', false);   // 1,234.56

// Отримання курсу
$rate = currency_rate('USD');           // середній
$rate = currency_rate('USD', 'buy');    // купівля

// Отримання символу валюти
$symbol = currency_symbol('USD'); // '$'
$symbol = currency_symbol('UAH'); // '₴'
```

## Blade-директиви

```blade
{{-- Конвертація та виведення --}}
@currency(100, 'USD', 'EUR')

{{-- Форматування із символом --}}
@currencyFormat(1234.56, 'USD')

{{-- Виведення курсу --}}
@currencyRate('USD')

{{-- Виведення символу валюти --}}
@currencySymbol('USD')
```

## Обробка подій

Пакет відправляє подію `CurrencyRateFetchFailed`, коли API-запит завершується невдачею.

### Створення слухача

```php
// app/Listeners/HandleCurrencyRateFailure.php
namespace App\Listeners;

use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HandleCurrencyRateFailure
{
    public function handle(CurrencyRateFetchFailed $event): void
    {
        Log::error('Currency API недоступний', [
            'provider'       => $event->providerClass,
            'error'          => $event->errorMessage,
            'using_fallback' => $event->usingFallback,
        ]);

        // Повідомити адміністратора лише при критичній помилці (немає fallback)
        if (!$event->usingFallback) {
            Notification::route('mail', 'admin@example.com')
                ->notify(new \App\Notifications\CurrencyApiDown($event));
        }
    }
}
```

### Реєстрація слухача

```php
// app/Providers/EventServiceProvider.php
use Fomvasss\Currency\Events\CurrencyRateFetchFailed;
use App\Listeners\HandleCurrencyRateFailure;

protected $listen = [
    CurrencyRateFetchFailed::class => [
        HandleCurrencyRateFailure::class,
    ],
];
```

### Властивості події

| Властивість      | Тип     | Опис                                              |
|------------------|---------|---------------------------------------------------|
| `$providerClass` | string  | Назва класу провайдера, що зазнав помилки         |
| `$errorMessage`  | string  | Опис помилки                                      |
| `$usingFallback` | bool    | Чи використовується резервний кеш                 |
| `$fallbackRates` | array   | Курси з резервного кешу (якщо доступні)           |

## Стратегія резервного провайдера

`jsdelivr` є ідеальним безкоштовним резервним варіантом, коли основний API недоступний:

```php
try {
    Currency::useProvider('monobank');
    $rate = Currency::getRate('USD');
} catch (\Exception $e) {
    // Безкоштовно, 150+ валют, без обмежень запитів
    Currency::useProvider('jsdelivr');
    $rate = Currency::getRate('USD');
}
```

**Переваги jsDelivr як резервного провайдера:**
- Безкоштовно, без обмежень запитів
- 150+ підтримуваних валют
- Швидка доставка через CDN
- Не потрібен API-ключ
- Оновлення щодня (не в реальному часі)

## Власні провайдери

Ви можете реалізувати власний провайдер курсів, розширивши `AbstractRateProvider` або безпосередньо реалізувавши контракт `RateProvider`.

Повний посібник з прикладами (провайдер з API-ключем, мультиджерельний fallback, mock для тестів): [CUSTOM_PROVIDERS.md](CUSTOM_PROVIDERS.md).

## Ліцензія

MIT License. Дивіться [LICENSE](LICENSE.md).

