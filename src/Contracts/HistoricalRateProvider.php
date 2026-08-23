<?php

namespace Fomvasss\Currency\Contracts;

interface HistoricalRateProvider extends RateProvider
{
    /**
     * Get exchange rates for all supported currencies as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @return array Array of currency codes with their rates ['USD' => ['buy' => 1.0, 'sell' => 1.0], ...]
     */
    public function getRatesAt(\DateTimeInterface $date): array;

    /**
     * Get exchange rate for specific currency as of a specific date.
     *
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @param \DateTimeInterface $date
     * @return array|null ['buy' => float, 'sell' => float] or null if not found
     */
    public function getRateAt(string $currency, \DateTimeInterface $date): ?array;
}
