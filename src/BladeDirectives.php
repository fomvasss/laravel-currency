<?php

namespace Fomvasss\Currency;

use Illuminate\Support\Facades\Blade;

class BladeDirectives
{
    /**
     * Register Blade directives.
     *
     * @return void
     */
    public static function register(): void
    {
        // @currency(100, 'USD', 'EUR')
        Blade::directive('currency', function ($expression) {
            return "<?php echo app('currency')->convert({$expression}); ?>";
        });

        // @currencyFormat(100, 'USD')
        Blade::directive('currencyFormat', function ($expression) {
            return "<?php echo app('currency')->format({$expression}); ?>";
        });

        // @currencyRate('USD')
        Blade::directive('currencyRate', function ($expression) {
            return "<?php echo app('currency')->getRate({$expression}); ?>";
        });

        // @currencySymbol('USD')
        Blade::directive('currencySymbol', function ($expression) {
            $code = "app('currency')->getCurrencyConfig({$expression})['symbol'] ?? {$expression}";
            return "<?php echo {$code}; ?>";
        });
    }
}
