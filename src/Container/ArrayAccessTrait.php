<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * @uses \Symbiotic\Container\ArrayContainerInterface
 */
trait ArrayAccessTrait /*implements \Symbiotic\Container\ArrayContainerInterface */
{
    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::has()
     * @uses BaseContainerTrait::has()
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     *
     * @uses \Symbiotic\Container\BaseContainerInterface::get()
     * @uses BaseContainerTrait::get()
     */
    public function offsetGet($key)
    {
        return $this->get($key);
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
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
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
    public function offsetUnset($key)
    {
        $this->delete($key);
    }
}
