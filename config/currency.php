<?php

return [
    /**
     * Default currency.
     */
    'default' => 'UAH',
    
    /**
     * Default rate provider.
     * Use a key from the 'providers' array or provide a custom class name that implements RateProvider interface.
     */
    'default_provider' => env('CURRENCY_DEFAULT_PROVIDER', 'monobank'),
    
    /**
     * Available rate providers.
     * Key-value pairs where key is the provider name and value is the class name.
     */
    'providers' => [
        'nbu' => \Fomvasss\Currency\RateProviders\NbuRateProvider::class,
        'monobank' => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class,
        'privatbank' => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class,
        'jsdelivr' => \Fomvasss\Currency\RateProviders\JsDelivrProvider::class,
        'exchangeratesapi' => \Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider::class,
        'currencyapi' => \Fomvasss\Currency\RateProviders\CurrencyApiProvider::class,
        'fixer' => \Fomvasss\Currency\RateProviders\FixerProvider::class,
    ],
    
    /**
     * Cache TTL in seconds for currency rates.
     */
    'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600),
    
    /**
     * Default rate type for currency conversion.
     * Options: 'buy', 'sell', 'average'
     * - 'buy' - Bank buying rate (you sell to bank)
     * - 'sell' - Bank selling rate (you buy from bank)
     * - 'average' - Average of buy and sell rates
     */
    'default_rate_type' => env('CURRENCY_DEFAULT_RATE_TYPE', 'average'),

    /**
     * Default number of decimal places for currency amounts.
     * This setting can be overridden by individual currency precision settings.
     */
    'default_precision' => env('CURRENCY_DEFAULT_PRECISION', 2),
    
    /**
     * 
     * API key for ExchangeRatesAPI (optional, can use free alternative)
     * Get your key at: https://exchangeratesapi.io/
     */
    'exchange_rates_api_key' => env('EXCHANGE_RATES_API_KEY', null),
    
    /**
     * API key for CurrencyAPI
     * Get your key at: https://currencyapi.com/
     * Free tier: 300 requests/month
     */
    'currencyapi_key' => env('CURRENCYAPI_KEY', null),
    
    /**
     * API key for Fixer.io
     * Get your key at: https://fixer.io/
     * Free tier: 100 requests/month
     */
    'fixer_api_key' => env('FIXER_API_KEY', null),
    
    /**
     * Active currencies configuration.
     * 
     * Only currencies listed here are active and available in the application.
     * To add more currencies, uncomment them from the list below.
     * 
     * Fields:
     * - code: Currency ISO code (required)
     * - title: Full currency name (required)
     * - symbol: Currency symbol for formatting (required)
     * - precision: Decimal places for amounts (required)
     * - thousandSeparator: Thousands separator (required)
     * - decimalSeparator: Decimal separator (required)
     * - symbolPlacement: 'before' or 'after' (required)
     */
    'currencies' => [
        'EUR' => [
            'code' => 'EUR',
            'title' => 'Euro',
            'symbol' => '€',
            'precision' => 2,
            'thousandSeparator' => ' ',
            'decimalSeparator' => ',',
            'symbolPlacement' => 'before',
        ],
        'PLN' => [
            'code' => 'PLN',
            'title' => 'Poland, Zloty',
            'symbol' => 'zł',
            'precision' => 2,
            'thousandSeparator' => ' ',
            'decimalSeparator' => ',',
            'symbolPlacement' => 'after',
        ],
        'UAH' => [
            'code' => 'UAH',
            'title' => 'Ukraine, Hryvnia',
            'symbol' => '₴',
            'precision' => 2,
            'thousandSeparator' => ' ',
            'decimalSeparator' => ',',
            'symbolPlacement' => 'after',
        ],
        'USD' => [
            'code' => 'USD',
            'title' => 'US Dollar',
            'symbol' => '$',
            'precision' => 2,
            'thousandSeparator' => ',',
            'decimalSeparator' => '.',
            'symbolPlacement' => 'before',
        ],
        // Uncomment currencies below to activate them
//        'ARS' => [
//            'code' => 'ARS',
//            'title' => 'Argentine Peso',
//            'symbol' => 'AR$',
//            'precision' => 2,
//            'thousandSeparator' => '.',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'before',
//        ],
//        'AMD' => [
//            'code' => 'AMD',
//            'title' => 'Armenian Dram',
//            'symbol' => 'Դ',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'AUD' => [
//            'code' => 'AUD',
//            'title' => 'Australian Dollar',
//            'symbol' => 'AU$',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'CAD' => [
//            'code' => 'CAD',
//            'title' => 'Canadian Dollar',
//            'symbol' => 'CA$',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'CHF' => [
//            'code' => 'CHF',
//            'title' => 'Swiss Franc',
//            'symbol' => 'Fr',
//            'precision' => 2,
//            'thousandSeparator' => '\'',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'CNY' => [
//            'code' => 'CNY',
//            'title' => 'China Yuan Renminbi',
//            'symbol' => '¥',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'CZK' => [
//            'code' => 'CZK',
//            'title' => 'Czech Koruna',
//            'symbol' => 'Kč',
//            'precision' => 2,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'after',
//        ],
//        'DKK' => [
//            'code' => 'DKK',
//            'title' => 'Danish Krone',
//            'symbol' => 'kr',
//            'precision' => 2,
//            'thousandSeparator' => '.',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'after',
//        ],
//        'GBP' => [
//            'code' => 'GBP',
//            'title' => 'Pound Sterling',
//            'symbol' => '£',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'HUF' => [
//            'code' => 'HUF',
//            'title' => 'Hungary, Forint',
//            'symbol' => 'Ft',
//            'precision' => 0,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => '',
//            'symbolPlacement' => 'after',
//        ],
//        'INR' => [
//            'code' => 'INR',
//            'title' => 'Indian Rupee',
//            'symbol' => '₹',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'JPY' => [
//            'code' => 'JPY',
//            'title' => 'Japan, Yen',
//            'symbol' => '¥',
//            'precision' => 0,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '',
//            'symbolPlacement' => 'before',
//        ],
//        'KRW' => [
//            'code' => 'KRW',
//            'title' => 'South Korea, Won',
//            'symbol' => '₩',
//            'precision' => 0,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '',
//            'symbolPlacement' => 'before',
//        ],
//        'MXN' => [
//            'code' => 'MXN',
//            'title' => 'Mexican Peso',
//            'symbol' => 'MX$',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'NOK' => [
//            'code' => 'NOK',
//            'title' => 'Norwegian Krone',
//            'symbol' => 'kr',
//            'precision' => 2,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'before',
//        ],
//        'NZD' => [
//            'code' => 'NZD',
//            'title' => 'New Zealand Dollar',
//            'symbol' => 'NZ$',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'RUB' => [
//            'code' => 'RUB',
//            'title' => 'Russian Ruble',
//            'symbol' => '₽',
//            'precision' => 2,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'after',
//        ],
//        'SEK' => [
//            'code' => 'SEK',
//            'title' => 'Swedish Krona',
//            'symbol' => 'kr',
//            'precision' => 2,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'after',
//        ],
//        'SGD' => [
//            'code' => 'SGD',
//            'title' => 'Singapore Dollar',
//            'symbol' => 'S$',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'THB' => [
//            'code' => 'THB',
//            'title' => 'Thailand, Baht',
//            'symbol' => '฿',
//            'precision' => 2,
//            'thousandSeparator' => ',',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
//        'TRY' => [
//            'code' => 'TRY',
//            'title' => 'Turkish Lira',
//            'symbol' => '₺',
//            'precision' => 2,
//            'thousandSeparator' => '.',
//            'decimalSeparator' => ',',
//            'symbolPlacement' => 'before',
//        ],
//        'ZAR' => [
//            'code' => 'ZAR',
//            'title' => 'South Africa, Rand',
//            'symbol' => 'R',
//            'precision' => 2,
//            'thousandSeparator' => ' ',
//            'decimalSeparator' => '.',
//            'symbolPlacement' => 'before',
//        ],
    ]
];
