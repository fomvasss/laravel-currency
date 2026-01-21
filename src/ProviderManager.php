<?php

namespace Fomvasss\Currency;

use Fomvasss\Currency\Contracts\RateProvider;
use Illuminate\Contracts\Container\Container;

class ProviderManager
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * The configuration array.
     */
    protected array $config;

    /**
     * Create a new provider manager instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container['config']['currency'] ?? [];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config['default_provider'] ?? 'monobank';
    }

    /**
     * Get all configured providers.
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        return $this->config['providers'] ?? [];
    }

    /**
     * Create a provider instance by name.
     *
     * @param string $name
     * @return RateProvider
     */
    public function createProvider(string $name): RateProvider
    {
        $providers = $this->getAvailableProviders();
        
        if (!isset($providers[$name])) {
            throw new \InvalidArgumentException("Provider '{$name}' is not configured");
        }

        $providerClass = $providers[$name];
        
        if (!class_exists($providerClass)) {
            throw new \InvalidArgumentException("Provider class '{$providerClass}' does not exist");
        }

        $provider = $this->container->make($providerClass);
        
        if (!$provider instanceof RateProvider) {
            throw new \InvalidArgumentException("Provider class '{$providerClass}' must implement RateProvider interface");
        }

        return $provider;
    }

    /**
     * Get provider instance by name.
     *
     * @param string|null $name
     * @return RateProvider
     */
    public function provider(?string $name = null): RateProvider
    {
        $name = $name ?: $this->getDefaultDriver();
        return $this->createProvider($name);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->provider()->$method(...$parameters);
    }
}
