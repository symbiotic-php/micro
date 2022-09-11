<?php
declare(strict_types=1);

namespace Symbiotic\Container;

trait SingletonTrait
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static object $instance;

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance():static
    {
        return null === static::$instance
            ? static::$instance = new static()
            : static::$instance;
    }
}
