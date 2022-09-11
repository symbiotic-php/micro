<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

/**
 * Interface SimpleCacheInterface
 * @package Symbiotic\Cache
 * @todo    delete?
 */
interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * Returns data from the cache or from the result of \Closure execution
     *
     * The method searches the cache for data, if it does not find it,
     * it executes the value function, writes the result to the cache and returns the data
     *
     * @param string   $key
     * @param \Closure $value If a key is found in the cache, it will return from the cache or
     *                        return the result of the $value function and write it to the cache
     *
     * @param null|int $ttl   Optional. The TTL value of this item. If no value is sent and
     *                        the driver supports TTL then the library may set a default value
     *                        for it or let the driver take care of that.
     *
     * @return mixed
     */
    public function remember(string $key, \Closure $value, int $ttl = null): mixed;
}
