<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * @uses \Symbiotic\Container\ArrayContainerInterface
 */
trait ArrayAccessTrait /*implements \Symbiotic\Container\ArrayContainerInterface*/
{
    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     *
     * @return bool
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::has()
     * @uses BaseContainerTrait::has()
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::get()
     * @uses BaseContainerTrait::get()
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::set()
     * @uses BaseContainerTrait::set()
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     *
     * @return void
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::delete()
     * @uses BaseContainerTrait::delete()
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->delete($offset);
    }
}
