# Laravel Currency

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)

A Laravel package for currency conversion and exchange rate management with multiple rate providers.

## Features

- 💱 Currency conversion with buy/sell/average rates
- 🏦 Multiple rate providers (Monobank, PrivatBank, NBU, etc.)
- 💾 Automatic caching of exchange rates
- 🎨 Currency formatting with symbols
- ⚙️ Easy configuration

## Installation

```bash
composer require fomvasss/laravel-currency
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=currency-config
```

## Quick Start

### Basic Usage

```php
use Fomvasss\Currency\Facades\Currency;

// Convert currencies
$euros = Currency::convert(100, 'USD', 'EUR');

// Get exchange rates
$usdRate = Currency::getRate('USD');

// Format currency
$formatted = Currency::format(1234.56, 'USD'); // $1,234.56
```

### Switch Rate Providers

```php
// Method 1: Use configured aliases (recommended)
Currency::useProvider('nbu');      // National Bank of Ukraine
Currency::useProvider('monobank'); // Monobank
Currency::useProvider('privatbank'); // PrivatBank
Currency::useProvider('jsdelivr'); // jsDelivr CDN (150+ currencies, free)

// Method 2: Use setRateProvider with alias
Currency::setRateProvider('nbu');
Currency::setRateProvider('monobank');

// Method 3: Use setRateProvider with class name
Currency::setRateProvider(\Fomvasss\Currency\RateProviders\NbuRateProvider::class);

// Method 4: Use setRateProvider with instance
use Fomvasss\Currency\RateProviders\NbuRateProvider;
Currency::setRateProvider(new NbuRateProvider());

$rate = Currency::getRate('USD');
```

### Check Provider Capabilities

```php
// Check what currencies current provider supports
$supported = Currency::getSupportedCurrencies();
// Example: ['EUR', 'USD'] for PrivatBank or ['USD', 'EUR', 'GBP', 'CAD', ...] for NBU

$count = Currency::getSupportedCurrenciesCount();
// Example: 2 for PrivatBank, 30+ for NBU

// Check if specific currency is supported
if (Currency::isSupported('JPY')) {
    $rate = Currency::getRate('JPY');
} else {
    echo "JPY not supported by current provider";
}
```

## Configuration

Available providers in `config/currency.php`:

```php
'providers' => [
    'nbu' => \Fomvasss\Currency\RateProviders\NbuRateProvider::class,        // 30+ currencies
    'monobank' => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class, // Multiple currencies  
    'privatbank' => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class, // EUR, USD only
    'jsdelivr' => \Fomvasss\Currency\RateProviders\JsDelivrProvider::class,  // 150+ currencies (free CDN)
],
```

> **Note**: Different providers support different currencies:
> - **NBU** (National Bank): 30+ currencies including major and regional ones
> - **Monobank**: Multiple international currencies
> - **PrivatBank**: Limited to EUR and USD only (API limitation)
> - **jsDelivr**: 150+ currencies via free CDN (good fallback, updated daily)

Set your preferred provider and cache settings:

```php
'default_provider' => 'monobank',
'cache_ttl' => 3600, // 1 hour
'default_rate_type' => 'average', // buy, sell, average
```

## Environment Variables

```env
CURRENCY_DEFAULT_PROVIDER=monobank
CURRENCY_DEFAULT_RATE_TYPE=average
CURRENCY_CACHE_TTL=3600
```

## Advanced Usage

### Rate Types

```php
// Get specific rate types
$buyRate = Currency::getRate('USD', 'buy');
$sellRate = Currency::getRate('USD', 'sell');
$avgRate = Currency::getRate('USD', 'average');

// Convert with specific rate type
$amount = Currency::convert(100, 'USD', 'EUR', 'sell');
```

### Working with Multiple Currencies

```php
// Get all rates
$allRates = Currency::getRates();

// Get active currencies from config
$currencies = Currency::getActiveCurrencies();
$codes = Currency::getActiveCurrencyCodes(); // ['USD', 'EUR', 'UAH']

// Set custom base currency (overrides config)
Currency::setBaseCurrency('EUR');
$euros = Currency::convert(100, 'USD', 'GBP'); // Now uses EUR as base

// Get current base currency
$baseCurrency = Currency::getBaseCurrency(); // 'EUR'

// When you change base currency, all rates are automatically recalculated
Currency::setBaseCurrency('USD');
$rates = Currency::getRates(); 
// Now returns: ['EUR' => 1.09, 'GBP' => 1.27, 'UAH' => 0.023, ...]
// All rates are relative to USD (1 USD = X currency)
// USD itself is NOT in the array (it's the base, so rate = 1.0)
```

**Important:** When you set a custom base currency with `setBaseCurrency()`, all rates returned by `getRates()` and `getRate()` are automatically converted to be relative to that base currency.

## Custom Providers

Create your own rate provider by extending `AbstractRateProvider`:

```php
namespace App\Providers;

use Fomvasss\Currency\RateProviders\AbstractRateProvider;

class MyBankProvider extends AbstractRateProvider
{
    protected function getApiUrl(): string
    {
        return 'https://api.mybank.com/rates';
    }

    protected function parseResponse($response): array
    {
        // Parse your API response
        return [
            'USD' => ['buy' => 27.5, 'sell' => 28.0],
            'EUR' => ['buy' => 30.0, 'sell' => 30.5],
        ];
    }
}
```

Add it to config:

```php
'providers' => [
    'mybank' => \App\Providers\MyBankProvider::class,
],
```

Use it:

```php
Currency::useProvider('mybank');
```

For detailed custom provider development, see [Custom Providers Guide](CUSTOM_PROVIDERS.md).

## Fallback Strategy

jsDelivr provider is excellent as a fallback when primary APIs are unavailable:

```php
try {
    Currency::useProvider('monobank');
    $rate = Currency::getRate('USD');
} catch (\Exception $e) {
    // Fallback to jsDelivr (free, no rate limits)
    Currency::useProvider('jsdelivr');
    $rate = Currency::getRate('USD');
}
```

**Benefits of jsDelivr as fallback:**
- ✅ Free with no rate limits
- ✅ 150+ currencies supported
- ✅ Fast CDN delivery
- ✅ No API keys needed
- ⚠️ Updated daily (not real-time)

## Requirements

- PHP 8.0+
- Laravel 9.x, 10.x, 11.x, or 12.x

## License

MIT License. See [LICENSE](LICENSE.md) for details.
