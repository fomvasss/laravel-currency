<?php

namespace Fomvasss\Currency;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/currency.php' => config_path('currency.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/currency.php', 'currency');

        $this->registerCurrency();

    }

    public function registerCurrency()
    {
        $this->app->singleton('currency', function ($app) {
            return new Currency($app->config->get('currency', []));
        });

        $this->app->alias(Currency::class, 'fomvasss-currency');
    }
}
