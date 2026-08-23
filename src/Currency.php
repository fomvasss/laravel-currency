<?php

namespace Fomvasss\Currency;

use Fomvasss\Currency\Contracts\RateProvider;

class Currency
{
    protected RateProvider $rateProvider;
    protected array $config;
    protected ?string $baseCurrency = null; // Override for base currency
    protected ProviderManager $providerManager;

    public function __construct(RateProvider $rateProvider, array $config = [], ?ProviderManager $providerManager = null)
    {
        $this->rateProvider = $rateProvider;
        $this->config = $config;
        $this->providerManager = $providerManager ?? app('currency.manager');
    }

    /**
     * Convert amount from one currency to another.
     *
     * @param float $amount Amount to convert
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @param string|null $rateType Rate type: 'buy', 'sell', or 'average'. If null, uses config default.
     * @return float Converted amount
     */
    public function convert(float $amount, string $from, string $to, ?string $rateType = null): float
    {
        $rateType = $this->resolveRateType($rateType);

        $from = strtoupper($from);
        $to = strtoupper($to);

        // Same currency, no conversion needed
        if ($from === $to) {
            return round($amount, $this->getPrecision($to));
        }

        $baseCurrency = $this->getBaseCurrency();

        // Convert from source to base currency
        if ($from !== $baseCurrency) {
            $fromRate = $this->getRateValue($from, $rateType);
            if ($fromRate === null) {
                throw new \InvalidArgumentException("Currency rate not found for: {$from}");
            }
            $amount = $amount * $fromRate;
        }

        // Convert from base currency to target
        if ($to !== $baseCurrency) {
            $toRate = $this->getRateValue($to, $rateType);
            if ($toRate === null) {
                throw new \InvalidArgumentException("Currency rate not found for: {$to}");
            }
            $amount = $amount / $toRate;
        }

        return round($amount, $this->getPrecision($to));
    }

    /**
     * Resolve rate type: explicit value, config default, or 'average'; validate it's allowed.
     *
     * @param string|null $rateType
     * @param bool $allowAll Whether 'all' is an allowed value (only getRates() supports it)
     * @return string
     */
    protected function resolveRateType(?string $rateType, bool $allowAll = false): string
    {
        $rateType = $rateType ?? ($this->config['default_rate_type'] ?? 'average');

        $allowed = $allowAll ? ['buy', 'sell', 'average', 'all'] : ['buy', 'sell', 'average'];

        if (!in_array($rateType, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid rate type: {$rateType}");
        }

        return $rateType;
    }

    /**
     * Get rate value based on type (buy, sell, or average).
     *
     * @param string $currency Currency code
     * @param string $rateType Rate type: 'buy', 'sell', or 'average'
     * @return float|null
     */
    protected function getRateValue(string $currency, string $rateType = 'average'): ?float
    {
        $providerBaseCurrency = $this->rateProvider->getBaseCurrency();
        $currentBaseCurrency = $this->getBaseCurrency();

        // If requesting rate for current base currency, return 1.0
        if ($currency === $currentBaseCurrency) {
            return 1.0;
        }

        // If base currency changed, we need to recalculate
        if ($currentBaseCurrency !== $providerBaseCurrency) {
            // Get rate for the requested currency from provider (relative to provider's base)
            $rate = $this->rateProvider->getRate($currency);
            if (!$rate) {
                // Check if requesting the provider's base currency
                if ($currency === $providerBaseCurrency) {
                    // Get the custom base currency rate and invert it
                    $baseRate = $this->rateProvider->getRate($currentBaseCurrency);
                    if (!$baseRate) {
                        return null;
                    }
                    return match ($rateType) {
                        'buy' => 1 / $baseRate['sell'],
                        'sell' => 1 / $baseRate['buy'],
                        'average' => 2 / ($baseRate['buy'] + $baseRate['sell']),
                    };
                }
                return null;
            }

            // Get the custom base currency rate from provider
            $baseRate = $this->rateProvider->getRate($currentBaseCurrency);
            if (!$baseRate) {
                return null;
            }

            // Convert: if 1 USD = 43 UAH and 1 EUR = 47 UAH, then 1 EUR = 47/43 USD
            return match ($rateType) {
                'buy' => $rate['buy'] / $baseRate['sell'],
                'sell' => $rate['sell'] / $baseRate['buy'],
                'average' => ($rate['buy'] + $rate['sell']) / ($baseRate['buy'] + $baseRate['sell']),
            };
        }

        // Normal flow - use provider's base currency
        $rate = $this->rateProvider->getRate($currency);

        if (!$rate) {
            return null;
        }

        return match ($rateType) {
            'buy' => $rate['buy'],
            'sell' => $rate['sell'],
            'average' => ($rate['buy'] + $rate['sell']) / 2,
        };
    }

    /**
     * Get exchange rate for specific currency relative to base currency.
     *
     * @param string $currency Currency code
     * @param string|null $rateType Rate type: 'buy', 'sell', or 'average'. If null, uses config default.
     * @return float|null
     */
    public function getRate(string $currency, ?string $rateType = null): ?float
    {
        return $this->getRateValue(strtoupper($currency), $this->resolveRateType($rateType));
    }

    /**
     * Get all exchange rates relative to base currency.
     *
     * @param string|null $rateType Rate type: 'buy', 'sell', 'average', or 'all'. If null, uses config default.
     * @return array
     */
    public function getRates(?string $rateType = null): array
    {
        $rateType = $this->resolveRateType($rateType, true);

        $rates = $this->rateProvider->getRates();
        $providerBaseCurrency = $this->rateProvider->getBaseCurrency();
        $currentBaseCurrency = $this->getBaseCurrency();

        // If custom base currency is set and differs from provider's base currency
        // we need to convert all rates relative to the new base currency
        if ($currentBaseCurrency !== $providerBaseCurrency) {
            if (!isset($rates[$currentBaseCurrency])) {
                throw new \InvalidArgumentException("Currency rate not found for: {$currentBaseCurrency}");
            }

            $baseRateData = $rates[$currentBaseCurrency];
            $convertedRates = [];

            foreach ($rates as $currency => $rate) {
                // Skip the new base currency itself
                if ($currency === $currentBaseCurrency) {
                    continue;
                }

                // Convert rates: if 1 USD = 43 UAH and 1 EUR = 47 UAH
                // then 1 EUR = 47/43 = 1.093 USD
                $convertedRates[$currency] = [
                    'buy' => $rate['buy'] / $baseRateData['sell'], // Use inverse for buy
                    'sell' => $rate['sell'] / $baseRateData['buy'], // Use inverse for sell
                ];
            }

            // Add provider's original base currency to the result
            // e.g., if converting from UAH to USD base, add UAH rate
            $convertedRates[$providerBaseCurrency] = [
                'buy' => 1 / $baseRateData['sell'],
                'sell' => 1 / $baseRateData['buy'],
            ];

            $rates = $convertedRates;
        }

        // Format results based on rate type
        $result = [];
        foreach ($rates as $currency => $rate) {
            if ($rateType === 'all') {
                $result[$currency] = $rate;
            } else {
                $result[$currency] = match ($rateType) {
                    'buy' => $rate['buy'],
                    'sell' => $rate['sell'],
                    'average' => ($rate['buy'] + $rate['sell']) / 2,
                };
            }
        }

        return $result;
    }

    /**
     * Get array of active currencies from config.
     * Returns all currencies defined in config (uncommented).
     *
     * @return array
     */
    public function getActiveCurrencies(): array
    {
        $currencies = $this->config['currencies'] ?? [];

        return array_filter($currencies, fn($currency) => ($currency['active'] ?? true) === true);
    }

    /**
     * Get active currency codes.
     *
     * @return array
     */
    public function getActiveCurrencyCodes(): array
    {
        return array_keys($this->getActiveCurrencies());
    }

    /**
     * Format amount in specified currency.
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @param bool $includeSymbol Include currency symbol
     * @return string Formatted amount
     */
    public function format(float $amount, string $currency, bool $includeSymbol = true): string
    {
        $currency = strtoupper($currency);
        $currencyConfig = $this->getCurrencyConfig($currency);

        $precision = $currencyConfig['precision'] ?? 2;
        $thousandSeparator = $currencyConfig['thousandSeparator'] ?? ',';
        $decimalSeparator = $currencyConfig['decimalSeparator'] ?? '.';
        $symbol = $currencyConfig['symbol'] ?? $currency;
        $symbolPlacement = $currencyConfig['symbolPlacement'] ?? 'before';

        $formatted = number_format($amount, $precision, $decimalSeparator, $thousandSeparator);

        if ($includeSymbol) {
            if ($symbolPlacement === 'after') {
                $formatted = $formatted . ' ' . $symbol;
            } else {
                $formatted = $symbol . ' ' . $formatted;
            }
        }

        return trim($formatted);
    }

    /**
     * Get currency configuration.
     *
     * @param string $currency Currency code
     * @return array
     */
    public function getCurrencyConfig(string $currency): array
    {
        $currency = strtoupper($currency);
        return $this->config['currencies'][$currency] ?? [];
    }

    /**
     * Get precision for currency.
     *
     * @param string $currency Currency code
     * @return int
     */
    public function getPrecision(string $currency): int
    {
        $config = $this->getCurrencyConfig($currency);
        return $config['precision'] ?? $this->config['default_precision'] ?? 2;
    }

    /**
     * Get default precision from config.
     *
     * @return int
     */
    public function getDefaultPrecision(): int
    {
        return $this->config['default_precision'] ?? 2;
    }

    /**
     * Get base currency code.
     *
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency ?? $this->config['default'] ?? $this->rateProvider->getBaseCurrency();
    }

    /**
     * Set base currency code.
     *
     * @param string $currency Currency code
     * @return $this
     */
    public function setBaseCurrency(string $currency): self
    {
        $this->baseCurrency = strtoupper($currency);
        
        return $this;
    }


    /**
     * Check if currency is supported.
     *
     * @param string $currency Currency code
     * @return bool
     */
    public function isSupported(string $currency): bool
    {
        return $this->rateProvider->supports(strtoupper($currency));
    }

    /**
     * Get all currencies from config.
     *
     * @return array
     */
    public function getAllCurrencies(): array
    {
        return $this->config['currencies'] ?? [];
    }

    /**
     * Get current rate provider.
     *
     * @return RateProvider
     */
    public function getProvider(): RateProvider
    {
        return $this->rateProvider;
    }

    /**
     * Alias for getProvider().
     *
     * @return RateProvider
     */
    public function getRateProvider(): RateProvider
    {
        return $this->getProvider();
    }

    /**
     * Get currencies supported by current provider.
     *
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return $this->rateProvider->getSupportedCurrencies();
    }

    /**
     * Get count of currencies supported by current provider.
     *
     * @return int
     */
    public function getSupportedCurrenciesCount(): int
    {
        return $this->rateProvider->getSupportedCurrenciesCount();
    }

    /**
     * Clear rate cache for current provider.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->rateProvider->clearCache();
    }

    /**
     * Set rate provider.
     * Accepts either a RateProvider instance, a provider alias from config, or a class name.
     *
     * @param RateProvider|string $provider
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setRateProvider($provider): self
    {
        $this->rateProvider = $this->providerManager->resolve($provider);
        return $this;
    }

    /**
     * Get available providers from config.
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        return $this->providerManager->getAvailableProviders();
    }

    /**
     * Switch to a different provider by name.
     *
     * @param string $providerName
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function useProvider(string $providerName): self
    {
        $this->rateProvider = $this->providerManager->createProvider($providerName);
        return $this;
    }
}
