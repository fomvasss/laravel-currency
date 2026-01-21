<?php

namespace Fomvasss\Currency;

use Fomvasss\Currency\Contracts\RateProvider;
use Fomvasss\Currency\RateProviders\MonobankRateProvider;
use Fomvasss\Currency\RateProviders\PrivatbankRateProvider;
use Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider;
use Fomvasss\Currency\RateProviders\CurrencyApiProvider;
use Fomvasss\Currency\RateProviders\FixerProvider;
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

        // Register providers array as a service
        $this->app->singleton('currency.providers', function ($app) {
            $providers = [];
            $configProviders = $app['config']['currency.providers'] ?? [];
            
            foreach ($configProviders as $name => $providerClass) {
                $providers[$name] = $app->make($providerClass);
            }
            
            return $providers;
        });


        // Register main Currency service
        $this->app->singleton('currency', function ($app) {
            $config = $app['config']['currency'];
            $provider = $this->resolveRateProvider($config['default_provider'] ?? 'monobank');

            return new Currency($provider, $config);
        });

        $this->app->alias('currency', Currency::class);
    }


    /**
     * Resolve rate provider from config or class name.
     *
     * @param string|object $provider Provider name or class instance
     * @return RateProvider
     */
    protected function resolveRateProvider($provider): RateProvider
    {
        // If it's already an instance
        if ($provider instanceof RateProvider) {
            return $provider;
        }

        // If it's a string (provider name or class name)
        if (is_string($provider)) {
            // Check if it's a configured provider name
            $providers = $this->app['config']['currency.providers'] ?? [];
            
            if (isset($providers[$provider])) {
                return $this->app->make($providers[$provider]);
            }


            // Try to resolve as class name directly
            if (class_exists($provider)) {
                return $this->app->make($provider);
            }
        }

        // Default fallback to monobank
        return new MonobankRateProvider();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'currency',
            'currency.manager',
            'currency.providers',
            Currency::class,
        ];
    }
}
