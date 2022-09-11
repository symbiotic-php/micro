<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * Interface BaseContainerInterface
 *
 * A less strict, augmented implementation.
 *
 * The name of the interface is specially different, so as not to be confused with the interface from PSR.
 * Using aliases is not recommended.
 *
 * @package Symbiotic\Container
 *
 * Extenders this interface
 * @see     ArrayContainerInterface
 * @see     MultipleAccessInterface
 * @see     MagicAccessInterface
 * @see     BaseContainerTrait
 *
 */
interface BaseContainerInterface
{

    /**
     * Get item by key
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key): mixed;

    /**
     * Checking the presence of data in the container by its key
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Put a key / value pair or array of key / value pairs.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;


    /**
     * Remove value by key
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool;

}
