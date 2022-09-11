<?php

declare(strict_types=1);

namespace Symbiotic\Container;


interface ServiceContainerInterface
{
    /**
     * Register a service provider with the application.
     *
     * @param object|string $provider {@see \Symbiotic\Core\ServiceProviderInterface}
     * @param bool          $force
     *
     * @return object
     */
    public function register(object|string $provider, bool $force = false): object;

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot(): void;


    /**
     * Get the registered service provider instance if it exists.
     *
     * @param string|object $provider
     *
     * @return object|null  if not found provider
     */
    public function getProvider(string|object $provider): ?object;

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param object|string $provider
     *
     * @return array|object[]
     */
    public function getProviders(string|object $provider): array;

    /**
     * @param array|string[] $services
     *
     * @return void
     */
    public function setDeferred(array $services): void;

    /**
     * @param string $service
     *
     * @return bool
     */
    public function isDeferService(string $service): bool;

    /**
     * @param string $service
     *
     * @return bool
     */
    public function loadDefer(string $service): bool;
}
