<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

use Psr\SimpleCache\CacheInterface;


interface CacheManagerInterface
{
    /**
     * Adding a Storage Builder
     *
     * @param string   $name
     * @param callable $builder function(array $config, ContainerInterface $container): CacheInterface;
     *
     * @return void
     */
    public function addDriver(string $name, callable $builder): void;

    /**
     * Returns the storage built on the basis of its config
     *
     * @param string|null $name
     *
     * @return CacheInterface
     */
    public function store(string $name = null): CacheInterface;
}