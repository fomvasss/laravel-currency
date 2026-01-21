<?php

namespace Fomvasss\Currency\Console\Commands;

use Fomvasss\Currency\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:rates 
                            {--provider= : Rate provider to use (monobank, privatbank, exchangeratesapi, currencyapi, fixer)}
                            {--currency= : Specific currency to show}
                            {--refresh : Clear cache and fetch fresh rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current exchange rates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Currency $currency): int
    {
        if ($this->option('refresh')) {
            $this->info('Clearing currency rates cache...');
            Cache::forget('currency_rates_MonobankRateProvider');
            Cache::forget('currency_rates_PrivatbankRateProvider');
            Cache::forget('currency_rates_ExchangeRatesApiProvider');
            Cache::forget('currency_rates_CurrencyApiProvider');
            Cache::forget('currency_rates_FixerProvider');
            $this->info('Cache cleared!');
        }

        // Change provider if specified
        if ($provider = $this->option('provider')) {
            try {
                $providerClass = $this->resolveProviderClass($provider);
                $currency->setRateProvider(new $providerClass());
                $this->info("Using provider: {$provider}");
            } catch (\Exception $e) {
                $this->error("Invalid provider: {$provider}");
                return 1;
            }
        }

        $this->info('Base currency: ' . $currency->getBaseCurrency());
        $this->info('Provider: ' . class_basename($currency->getRateProvider()));
        $this->line('');

        // Show specific currency or all
        if ($currencyCode = $this->option('currency')) {
            $this->showCurrency($currency, strtoupper($currencyCode));
        } else {
            $this->showAllCurrencies($currency);
        }

        return 0;
    }

    /**
     * Show specific currency rates.
     *
     * @param Currency $currency
     * @param string $code
     * @return void
     */
    protected function showCurrency(Currency $currency, string $code): void
    {
        $rate = $currency->getRate($code, 'all');

        if (!$rate) {
            $this->error("Currency {$code} not found or not supported by current provider.");
            return;
        }

        $config = $currency->getCurrencyConfig($code);

        $this->table(
            ['Property', 'Value'],
            [
                ['Code', $code],
                ['Title', $config['title'] ?? 'N/A'],
                ['Symbol', $config['symbol'] ?? 'N/A'],
                ['Buy Rate', $rate['buy'] ?? 'N/A'],
                ['Sell Rate', $rate['sell'] ?? 'N/A'],
                ['Average', number_format(($rate['buy'] + $rate['sell']) / 2, 4)],
            ]
        );
    }

    /**
     * Show all currency rates.
     *
     * @param Currency $currency
     * @return void
     */
    protected function showAllCurrencies(Currency $currency): void
    {
        $rates = $currency->getRates('all');

        if (empty($rates)) {
            $this->warn('No rates available.');
            return;
        }

        $rows = [];
        foreach ($rates as $code => $rate) {
            $config = $currency->getCurrencyConfig($code);
            $average = ($rate['buy'] + $rate['sell']) / 2;

            $rows[] = [
                $code,
                $config['symbol'] ?? '',
                number_format($rate['buy'], 4),
                number_format($rate['sell'], 4),
                number_format($average, 4),
            ];
        }

        $this->table(
            ['Currency', 'Symbol', 'Buy', 'Sell', 'Average'],
            $rows
        );

        $this->line('');
        $this->info('Total currencies: ' . count($rates));
    }

    /**
     * Resolve provider class from name.
     *
     * @param string $name
     * @return string
     */
    protected function resolveProviderClass(string $name): string
    {
        $providers = [
            'monobank' => \Fomvasss\Currency\RateProviders\MonobankRateProvider::class,
            'privatbank' => \Fomvasss\Currency\RateProviders\PrivatbankRateProvider::class,
            'exchangeratesapi' => \Fomvasss\Currency\RateProviders\ExchangeRatesApiProvider::class,
            'currencyapi' => \Fomvasss\Currency\RateProviders\CurrencyApiProvider::class,
            'fixer' => \Fomvasss\Currency\RateProviders\FixerProvider::class,
        ];

        return $providers[strtolower($name)] ?? $name;
    }
}
