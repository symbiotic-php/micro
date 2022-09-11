<?php

declare(strict_types=1);

namespace Symbiotic\Container;

/**
 * Interface MultipleAccessInterface
 *
 * @package Symbiotic\Container
 *
 */
interface MultipleAccessInterface
{

    /**
     *
     * @param iterable $keys
     *
     * @return \ArrayAccess|array
     */
    public function getMultiple(iterable $keys): \ArrayAccess|array;

    /**
     * Set array of key / value pairs.
     *
     * @param iterable $values [ key => value, key2=> val2]
     *
     * @return void
     * @uses set()
     */
    public function setMultiple(iterable $values): void;

    /**
     * Delete multiple values by key
     *
     * @param iterable $keys
     *
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool;
}
