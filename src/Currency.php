<?php

namespace Fomvasss\Currency;

use Fomvasss\Currency\Contracts\HistoricalRateProvider;
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
     * Convert amount from one currency to another using rates as of a specific date.
     *
     * @param float $amount Amount to convert
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @param \DateTimeInterface $date
     * @param string|null $rateType Rate type: 'buy', 'sell', or 'average'. If null, uses config default.
     * @return float Converted amount
     */
    public function convertAt(float $amount, string $from, string $to, \DateTimeInterface $date, ?string $rateType = null): float
    {
        $this->assertSupportsHistoricalRates();

        $rateType = $this->resolveRateType($rateType);

        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return round($amount, $this->getPrecision($to));
        }

        $baseCurrency = $this->getBaseCurrency();
        $rates = $this->rateProvider->getRatesAt($date);
        $providerBaseCurrency = $this->rateProvider->getBaseCurrency();

        if ($from !== $baseCurrency) {
            $fromRate = $this->getRateValueAt($rates, $providerBaseCurrency, $from, $rateType);
            if ($fromRate === null) {
                throw new \InvalidArgumentException("Currency rate not found for: {$from} at {$date->format('Y-m-d')}");
            }
            $amount = $amount * $fromRate;
        }

        if ($to !== $baseCurrency) {
            $toRate = $this->getRateValueAt($rates, $providerBaseCurrency, $to, $rateType);
            if ($toRate === null) {
                throw new \InvalidArgumentException("Currency rate not found for: {$to} at {$date->format('Y-m-d')}");
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
        return $this->resolveRateValue(
            fn (string $c) => $this->rateProvider->getRate($c),
            $this->rateProvider->getBaseCurrency(),
            $currency,
            $rateType
        );
    }

    /**
     * Get rate value based on type (buy, sell, or average) from a given set of rates
     * (e.g. rates as of a specific date), instead of querying the provider directly.
     *
     * @param array $rates
     * @param string $providerBaseCurrency
     * @param string $currency Currency code
     * @param string $rateType Rate type: 'buy', 'sell', or 'average'
     * @return float|null
     */
    protected function getRateValueAt(array $rates, string $providerBaseCurrency, string $currency, string $rateType): ?float
    {
        return $this->resolveRateValue(
            fn (string $c) => $rates[$c] ?? null,
            $providerBaseCurrency,
            $currency,
            $rateType
        );
    }

    /**
     * Shared rate resolution logic behind getRateValue()/getRateValueAt(): given a way to
     * look up a currency's ['buy' => ..., 'sell' => ...] rate, resolve the rate relative to
     * the current base currency (which may differ from the provider's own base currency).
     *
     * @param callable $getRate fn(string $currency): ?array
     * @param string $providerBaseCurrency
     * @param string $currency Currency code
     * @param string $rateType Rate type: 'buy', 'sell', or 'average'
     * @return float|null
     */
    protected function resolveRateValue(callable $getRate, string $providerBaseCurrency, string $currency, string $rateType): ?float
    {
        $currentBaseCurrency = $this->getBaseCurrency();

        // If requesting rate for current base currency, return 1.0
        if ($currency === $currentBaseCurrency) {
            return 1.0;
        }

        // If base currency changed, we need to recalculate
        if ($currentBaseCurrency !== $providerBaseCurrency) {
            // Get rate for the requested currency (relative to provider's base)
            $rate = $getRate($currency);
            if (!$rate) {
                // Check if requesting the provider's base currency
                if ($currency === $providerBaseCurrency) {
                    // Get the custom base currency rate and invert it
                    $baseRate = $getRate($currentBaseCurrency);
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

            // Get the custom base currency rate
            $baseRate = $getRate($currentBaseCurrency);
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
        $rate = $getRate($currency);

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
     * Get exchange rate for specific currency relative to base currency, as of a specific date.
     *
     * @param string $currency Currency code
     * @param \DateTimeInterface $date
     * @param string|null $rateType Rate type: 'buy', 'sell', or 'average'. If null, uses config default.
     * @return float|null
     */
    public function getRateAt(string $currency, \DateTimeInterface $date, ?string $rateType = null): ?float
    {
        $this->assertSupportsHistoricalRates();

        $currency = strtoupper($currency);
        $rateType = $this->resolveRateType($rateType);

        $rates = $this->rateProvider->getRatesAt($date);
        $providerBaseCurrency = $this->rateProvider->getBaseCurrency();

        return $this->getRateValueAt($rates, $providerBaseCurrency, $currency, $rateType);
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

        return $this->formatRates($rates, $providerBaseCurrency, $rateType);
    }

    /**
     * Get all exchange rates relative to base currency, as of a specific date.
     *
     * @param \DateTimeInterface $date
     * @param string|null $rateType Rate type: 'buy', 'sell', 'average', or 'all'. If null, uses config default.
     * @return array
     */
    public function getRatesAt(\DateTimeInterface $date, ?string $rateType = null): array
    {
        $this->assertSupportsHistoricalRates();

        $rateType = $this->resolveRateType($rateType, true);

        $rates = $this->rateProvider->getRatesAt($date);
        $providerBaseCurrency = $this->rateProvider->getBaseCurrency();

        return $this->formatRates($rates, $providerBaseCurrency, $rateType);
    }

    /**
     * Shared formatting logic behind getRates()/getRatesAt(): recalculate a set of rates
     * relative to the current base currency (if it differs from the provider's own base
     * currency) and reduce each entry to the requested rate type.
     *
     * @param array $rates
     * @param string $providerBaseCurrency
     * @param string $rateType Rate type: 'buy', 'sell', 'average', or 'all'
     * @return array
     */
    protected function formatRates(array $rates, string $providerBaseCurrency, string $rateType): array
    {
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

    /**
     * Check whether the current rate provider supports historical (per-date) rates.
     *
     * @return bool
     */
    public function supportsHistoricalRates(): bool
    {
        return $this->rateProvider instanceof HistoricalRateProvider;
    }

    /**
     * @return void
     * @throws \LogicException
     */
    protected function assertSupportsHistoricalRates(): void
    {
        if (!$this->supportsHistoricalRates()) {
            throw new \LogicException(class_basename($this->rateProvider) . ' does not support historical rates');
        }
    }
}
