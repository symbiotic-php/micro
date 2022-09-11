<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * Trait MagicAccessTrait
 * @package Symbiotic\Container
 *
 * @method bool has(string $key)
 * @uses    \Symbiotic\Container\BaseContainerInterface::has()
 * @uses    BaseContainerTrait::has()
 *
 * @method mixed|null get(string $key)
 * @uses    \Symbiotic\Container\BaseContainerInterface::get()
 * @uses    BaseContainerTrait::get()
 *
 * @method void set(string $key, $value)
 * @uses    \Symbiotic\Container\BaseContainerInterface::set()
 * @uses    BaseContainerTrait::set()
 *
 * @method bool delete(string $key)
 * @uses    \Symbiotic\Container\BaseContainerInterface::delete()
 * @uses    BaseContainerTrait::delete()
 */
trait MagicAccessTrait /*implements \Symbiotic\Container\MagicAccessInterface*/
{

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function __unset(string $key): void
    {
        $this->delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Special get Method with default
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return mixed|null
     */
    public function __invoke(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->get($key) : (\is_callable($default) ? $default() : $default);
    }
}