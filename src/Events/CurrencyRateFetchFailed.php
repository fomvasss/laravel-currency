<?php

namespace Fomvasss\Currency\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencyRateFetchFailed
{
    use Dispatchable, SerializesModels;

    public string $providerClass;
    public string $errorMessage;
    public bool $usingFallback;
    public ?array $fallbackRates;

    /**
     * Create a new event instance.
     *
     * @param string $providerClass Provider class name
     * @param string $errorMessage Error message
     * @param bool $usingFallback Whether fallback cache is being used
     * @param array|null $fallbackRates Fallback rates being used (if any)
     */
    public function __construct(
        string $providerClass,
        string $errorMessage,
        bool $usingFallback = false,
        ?array $fallbackRates = null
    ) {
        $this->providerClass = $providerClass;
        $this->errorMessage = $errorMessage;
        $this->usingFallback = $usingFallback;
        $this->fallbackRates = $fallbackRates;
    }
}
