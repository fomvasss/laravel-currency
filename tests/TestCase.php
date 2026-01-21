<?php

namespace Fomvasss\Currency\Tests;

use Fomvasss\Currency\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Currency' => \Fomvasss\Currency\Facades\Currency::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default configuration
        $app['config']->set('currency.default', 'UAH');
        $app['config']->set('currency.default_provider', 'monobank');
        $app['config']->set('currency.cache_ttl', 3600);

        // Setup test currencies
        $app['config']->set('currency.currencies', [
            'UAH' => [
                'code' => 'UAH',
                'title' => 'Ukrainian Hryvnia',
                'symbol' => '₴',
                'precision' => 2,
                'thousandSeparator' => ' ',
                'decimalSeparator' => '.',
                'symbolPlacement' => 'after',
                'active' => true,
            ],
            'USD' => [
                'code' => 'USD',
                'title' => 'US Dollar',
                'symbol' => '$',
                'precision' => 2,
                'thousandSeparator' => ',',
                'decimalSeparator' => '.',
                'symbolPlacement' => 'before',
                'active' => true,
            ],
            'EUR' => [
                'code' => 'EUR',
                'title' => 'Euro',
                'symbol' => '€',
                'precision' => 2,
                'thousandSeparator' => '.',
                'decimalSeparator' => ',',
                'symbolPlacement' => 'before',
                'active' => true,
            ],
            'GBP' => [
                'code' => 'GBP',
                'title' => 'British Pound',
                'symbol' => '£',
                'precision' => 2,
                'thousandSeparator' => ',',
                'decimalSeparator' => '.',
                'symbolPlacement' => 'before',
                'active' => false,
            ],
        ]);
    }
}
