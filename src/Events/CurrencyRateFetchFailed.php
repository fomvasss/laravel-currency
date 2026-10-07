<?php

namespace Fomvasss\Currency\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CurrencyRateFetchFailed
{
    use Dispatchable;

    public string $providerClass;
    public string $errorMessage;
    public bool $usingFallback;
    public ?array $fallbackRates;
    public ?\DateTimeInterface $date;

    /**
     * Create a new event instance.
     *
     * @param string $providerClass Provider class name
     * @param string $errorMessage Error message
     * @param bool $usingFallback Whether fallback cache is being used
     * @param array|null $fallbackRates Fallback rates being used (if any)
     * @param \DateTimeInterface|null $date The historical date the fetch was for, if applicable
     */
    public function __construct(
        string $providerClass,
        string $errorMessage,
        bool $usingFallback = false,
        ?array $fallbackRates = null,
        ?\DateTimeInterface $date = null
    ) {
        $this->providerClass = $providerClass;
        $this->errorMessage = $errorMessage;
        $this->usingFallback = $usingFallback;
        $this->fallbackRates = $fallbackRates;
        $this->date = $date;
    }
}
