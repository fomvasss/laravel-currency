# Laravel Currency

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-currency.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-currency)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-currency.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-currency)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-currency.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-currency)

Usage and manage currency in your Laravel application

----------

## Installation

Run from the command line:

```bash
composer require fomvasss/laravel-currency
```

## Publishing

```bash
php artisan vendor:publish --provider="Fomvasss\Currency\ServiceProvider"
```

## Usage
### Usage facade `\Fomvasss\Currency\Facades\Currency`

```php
Currency::getActiveCurrencies();
Currency::issetCurrency('USD');
Currency::getCurrency('RUB');
Currency::format(120, 'RUB');
Currency::convert(10.00, 'USD', 'UAH', false);
Currency::format(123, 'RUB');
```

## Links
- Like package [Torann/laravel-currency](https://github.com/Torann/laravel-currency)