<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * Trait MultipleAccessTrait
 *
 * @package Symbiotic/container-tarits
 *
 */
trait MultipleAccessTrait /*implements \Symbiotic\Container\MultipleAccessInterface*/
{
    /**
     * @param iterable $keys array keys
     *
     * @return array|\ArrayAccess
     * @throws BindingResolutionException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getMultiple(iterable $keys): array|\ArrayAccess
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Set array of key / value pairs.
     *
     * @param iterable $values [ key => value, key2=> val2]
     *
     * @return void
     * @uses set()
     */
    public function setMultiple(iterable $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param iterable $keys keys array [key1,key2,....]
     *
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $result = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }
}
