<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * Trait BaseContainerTrait
 *
 * Because of {@see BaseContainerTrait::getContainerItems} works 30% longer than ItemsContainerTrait,
 * but it is more versatile
 * @see \Symbiotic\Container\BaseContainerInterface
 */
trait BaseContainerTrait /*implements \Symbiotic\Container\BaseContainerInterface*/
{

    /**
     * @param string         $key
     *
     * @param mixed|\Closure $default hidden param
     *
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        $items = &$this->getContainerItems();
        return $this->hasBy($key, $items) ? $items[$key] :
            (
            is_callable($default = \func_num_args() === 2 ? \func_get_arg(1) : null)
                ? $default() : $default
            );
    }

    /**
     * A special method for returning data by reference and managing it out
     *
     * @return array|\ArrayAccess
     * @todo: Can do protected, on the one hand it is convenient, but to give everyone in a row to manage is not
     *        correct!?
     */
    abstract protected function &getContainerItems(): array|\ArrayAccess;

    /**
     * @param string             $key
     * @param array|\ArrayAccess $items
     *
     * @return bool
     * @info
     */
    private function hasBy(string $key, \ArrayAccess|array $items): bool
    {
        return isset($items[$key]) // isset в 4 раза быстрее array_key_exists
            || (is_array($items) && array_key_exists($key, $items))
            || ($items instanceof \ArrayAccess && $items->offsetExists($key));
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $items = &$this->getContainerItems();
        return $this->hasBy($key, $items);
    }

    /**
     * @param string     $key
     * @param            $value
     */
    public function set(string $key, $value): void
    {
        $items = &$this->getContainerItems();
        $items[$key] = $value;
    }


    /**
     * @param string $key
     *
     * @return mixed
     */
    public function delete(string $key): bool
    {
        $items = &$this->getContainerItems();
        unset($items[$key]);

        return true;
    }

}
