<?php

namespace Fomvasss\Currency;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/currency.php' => config_path('currency.php'),
            ], 'currency-config');


            // Register commands
            $this->commands([
                Console\Commands\CurrencyRatesCommand::class,
                Console\Commands\CurrencyConvertCommand::class,
            ]);
        }

        // Register Blade directives
        BladeDirectives::register();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/currency.php',
            'currency'
        );

        // Register ProviderManager
        $this->app->singleton('currency.manager', function ($app) {
            return new ProviderManager($app);
        });

        // Register main Currency service
        $this->app->singleton('currency', function ($app) {
            $config = $app['config']['currency'];
            $manager = $app['currency.manager'];
            $provider = $manager->resolve($config['default_provider'] ?? 'monobank');

            return new Currency($provider, $config, $manager);
        });

        $this->app->alias('currency', Currency::class);
    }
}
