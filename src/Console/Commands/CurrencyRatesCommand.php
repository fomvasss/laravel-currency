<?php

namespace Fomvasss\Currency\Console\Commands;

use Fomvasss\Currency\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:rates
                            {--provider= : Rate provider to use (alias from config or class name)}
                            {--currency= : Specific currency to show}
                            {--date= : Show historical rates for this date (any format Carbon::parse understands)}
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
        // Change provider if specified
        if ($provider = $this->option('provider')) {
            try {
                $currency->setRateProvider($provider);
                $this->info("Using provider: {$provider}");
            } catch (\Exception $e) {
                $this->error("Invalid provider: {$provider}");
                return 1;
            }
        }

        if ($this->option('refresh')) {
            $this->info('Clearing currency rates cache...');
            $currency->clearCache();
            $this->info('Cache cleared!');
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        if ($date && !$currency->supportsHistoricalRates()) {
            $this->error(class_basename($currency->getRateProvider()) . ' does not support historical rates.');
            return 1;
        }

        $this->info('Base currency: ' . $currency->getBaseCurrency());
        $this->info('Provider: ' . class_basename($currency->getRateProvider()));
        if ($date) {
            $this->info('Date: ' . $date->format('Y-m-d'));
        }
        $this->line('');

        // Show specific currency or all
        if ($currencyCode = $this->option('currency')) {
            $this->showCurrency($currency, strtoupper($currencyCode), $date);
        } else {
            $this->showAllCurrencies($currency, $date);
        }

        return 0;
    }

    /**
     * Show specific currency rates.
     *
     * @param Currency $currency
     * @param string $code
     * @param \DateTimeInterface|null $date
     * @return void
     */
    protected function showCurrency(Currency $currency, string $code, ?\DateTimeInterface $date = null): void
    {
        $rate = $date
            ? ($currency->getRatesAt($date, 'all')[$code] ?? null)
            : ($currency->getRates('all')[$code] ?? null);

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
     * @param \DateTimeInterface|null $date
     * @return void
     */
    protected function showAllCurrencies(Currency $currency, ?\DateTimeInterface $date = null): void
    {
        $rates = $date ? $currency->getRatesAt($date, 'all') : $currency->getRates('all');

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
}
