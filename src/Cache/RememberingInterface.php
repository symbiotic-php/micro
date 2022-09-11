<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

interface RememberingInterface
{
    /**
     * Combined method of obtaining and storing data
     *
     * If there is data on the key in your storage, then you need to return it
     * If there is no data, then execute the function, write the data to the key storage and return it.
     *
     *
     * @param string   $key
     * @param callable $value function for getting data
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function remember(string $key, callable $value): mixed;
}