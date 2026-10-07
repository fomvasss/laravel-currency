# Laravel Currency

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)

Конвертація валют, курси й форматування сум для Laravel. Курси беруться з підключуваних провайдерів — Monobank, ПриватБанк, НБУ, jsDelivr currency-api, ExchangeRatesAPI, CurrencyAPI, Fixer або власного — і кешуються з резервним кешем на випадок, коли API недоступний.

[English](README.md)

Документація англійською — https://fomvasss.github.io/laravel-currency/ (ті самі сторінки, що в [docs/](docs/index.md)).

- **Конвертація** за курсом купівлі / продажу / середнім
- **Сім вбудованих провайдерів** з перемиканням під час роботи, плюс власні через простий контракт
- **Історичні курси** — конвертація за курсом на минулу дату
- **Зміна базової валюти** з автоматичним перерахунком курсів
- **Кешування** з довгим резервним кешем і подією `CurrencyRateFetchFailed`
- **Форматування** з символом, точністю й роздільниками для кожної валюти
- Хелпери, Blade-директиви, команди `currency:rates` і `currency:convert`

## Вимоги

- PHP ^8.1
- Laravel 9 – 13

## Встановлення

```bash
composer require fomvasss/laravel-currency

php artisan vendor:publish --tag=currency-config
```

```env
CURRENCY_DEFAULT_PROVIDER=monobank
```

## Швидкий старт

```php
use Fomvasss\Currency\Facades\Currency;

Currency::convert(100, 'USD', 'UAH');          // середній курс
Currency::convert(100, 'USD', 'UAH', 'sell');
Currency::getRate('EUR');                      // гривень за 1 EUR
Currency::format(1234.5, 'USD');               // "$ 1,234.50"

Currency::useProvider('nbu')->convertAt(100, 'USD', 'UAH', now()->subYear());
```

```bash
php artisan currency:rates --provider=nbu
php artisan currency:convert 100 usd eur --format
```

## Ліцензія

MIT — див. [LICENSE](LICENSE.md).

## Підтримка

Якщо цей пакет корисний для вас, підтримайте його розробку:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> Адреса USDT TRC20: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`
