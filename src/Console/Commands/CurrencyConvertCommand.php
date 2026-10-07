<?php

namespace Fomvasss\Currency\Console\Commands;

use Fomvasss\Currency\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CurrencyConvertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:convert
                            {amount : Amount to convert}
                            {from : Source currency code}
                            {to : Target currency code}
                            {--rate=average : Rate type (buy, sell, average)}
                            {--date= : Convert using the historical rate for this date (any format Carbon::parse understands)}
                            {--format : Format the output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert amount from one currency to another';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Currency $currency): int
    {
        $amount = (float) $this->argument('amount');
        $from = strtoupper($this->argument('from'));
        $to = strtoupper($this->argument('to'));
        $rateType = $this->option('rate');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        try {
            $converted = $date
                ? $currency->convertAt($amount, $from, $to, $date, $rateType)
                : $currency->convert($amount, $from, $to, $rateType);

            $this->line('');
            $this->info("Conversion Details:");
            $this->line("──────────────────────────────────");

            if ($this->option('format')) {
                $this->line("From: " . $currency->format($amount, $from));
                $this->line("To:   " . $currency->format($converted, $to));
            } else {
                $this->line("From: {$amount} {$from}");
                $this->line("To:   {$converted} {$to}");
            }

            $this->line("Rate Type: {$rateType}");

            if ($date) {
                $this->line("Date: " . $date->format('Y-m-d'));
                $rate = $currency->getRateAt($from, $date, $rateType);
            } else {
                $rate = $currency->getRate($from, $rateType);
            }

            if ($rate) {
                $this->line("Rate ({$from}): {$rate}");
            }

            $this->line('');

            return 0;
        } catch (\InvalidArgumentException $e) {
            $this->error("Conversion error: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return 1;
        }
    }
}
