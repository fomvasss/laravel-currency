<?php

namespace Fomvasss\Currency\Contracts;

interface RateProvider
{
    /**
     * Get exchange rates for all supported currencies.
     *
     * @return array Array of currency codes with their rates ['USD' => ['buy' => 1.0, 'sell' => 1.0], ...]
     */
    public function getRates(): array;

    /**
     * Get exchange rate for specific currency.
     *
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @return array|null ['buy' => float, 'sell' => float] or null if not found
     */
    public function getRate(string $currency): ?array;

    /**
     * Check if the provider supports given currency.
     *
     * @param string $currency Currency code
     * @return bool
     */
    public function supports(string $currency): bool;

    /**
     * Get the base currency code for this provider.
     *
     * @return string
     */
    public function getBaseCurrency(): string;

    /**
     * Get list of supported currency codes.
     *
     * @return array
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get count of supported currencies.
     *
     * @return int
     */
    public function getSupportedCurrenciesCount(): int;

    /**
     * Clear both regular and fallback cache for this provider.
     *
     * @return void
     */
    public function clearCache(): void;
}
