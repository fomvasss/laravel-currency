<?php

namespace Fomvasss\Currency\Console\Commands;

use Fomvasss\Currency\Currency;
use Illuminate\Console\Command;

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

        try {
            $converted = $currency->convert($amount, $from, $to, $rateType);

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

            $rate = $currency->getRate($from, $rateType);
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
