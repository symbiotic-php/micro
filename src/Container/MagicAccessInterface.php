<?php

declare(strict_types=1);

namespace Symbiotic\Container;

/**
 * Interface ArrayContainerInterface
 * @package Symbiotic\Container
 *
 * @see     \Symbiotic\Container\MagicAccessTrait  realisation trait (package: symbiotic/container-traits)
 */
interface MagicAccessInterface
{

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws  \Exception
     */
    public function __get(string $key);

    /**
     * Set item
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $key, mixed $value): void;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool;

    /**
     * @param string $key
     *
     * @return void
     */
    public function __unset(string $key): void;

    /**
     * Special get Method with default
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return mixed|null
     * @see \Psr\Container\ContainerInterface::get()
     */
    public function __invoke(string $key, mixed $default = null): mixed;
}
