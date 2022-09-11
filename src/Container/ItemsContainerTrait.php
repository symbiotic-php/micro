<?php

declare(strict_types=1);

namespace Symbiotic\Container;


/**
 * Trait ItemsContainerTrait
 *
 * Less versatile, but works 35% faster {@see BaseContainerTrait}
 *
 */
trait ItemsContainerTrait /* implements \Symbiotic\Container\BaseContainerInterface */
{

    /**
     * @var array
     */
    protected array $items = [];

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        $items = $this->items;
        return array_key_exists($key, $items) ? $items[$key] :
            (
            is_callable($default = \func_num_args() === 2 ? \func_get_arg(1) : null)
                ? $default() : $default
            );
    }


    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }


    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

}
