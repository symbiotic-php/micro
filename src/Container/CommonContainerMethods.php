<?php

declare(strict_types=1);

namespace Symbiotic\Container;

/**
 * @used-by \Symbiotic\Container\ContainerTrait
 * @used-by \Symbiotic\Container\SubContainerTrait
 */
trait CommonContainerMethods
{
    /**
     * Register an existing instance as shared in the container.
     *
     * @param string      $abstract
     * @param mixed       $instance
     * @param null|string $alias
     *
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance, string $alias = null): mixed
    {
        /**
         * @var \Symbiotic\Container\DIContainerInterface $this
         */
        if (isset($this->aliases[$abstract])) {
            foreach ($this->abstractAliases as $abstr => $aliases) {
                foreach ($aliases as $index => $alias) {
                    if ($alias == $abstract) {
                        unset($this->abstractAliases[$abstr][$index]);
                    }
                }
            }
        }
        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }
        if ($alias) {
            $this->alias($abstract, $alias);
        }

        return $instance;
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param string   $abstract
     * @param \Closure $closure
     *
     * @return void
     *
     */
    public function extend(string $abstract, \Closure $closure): void
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;
            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}