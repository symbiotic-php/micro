<?php

declare(strict_types=1);

namespace Symbiotic\Container;

use Closure;

/**
 * @used-by \Symbiotic\Container\ContainerTrait
 * @used-by \Symbiotic\Container\SubContainerTrait
 */
trait CommonContainerMethods
{

    /**
     * All of the global resolving callbacks.
     *
     * @var Closure[]
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * All the global after resolving callbacks.
     *
     * @var Closure[]
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * All the resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $resolvingCallbacks = [];

    /**
     * All the after resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $afterResolvingCallbacks = [];



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

    /**
     * Register a new resolving callback.
     *
     * @param Closure|string $abstract
     * @param callable|null  $callback closure or Invokable object
     *
     * @return void
     */
    public function resolving(string|Closure $abstract, callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && is_callable($abstract)) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     *
     * @param Closure|string $abstract
     * @param callable|null  $callback closure or Invokable object
     *
     * @return void
     */
    public function afterResolving(string|Closure $abstract, callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_callable($abstract) && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all the resolving callbacks.
     *
     * @param string $abstract
     * @param mixed  $object
     *
     * @return void
     */
    public function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireResolvingByData(
            $abstract,
            $object,
            $this->globalResolvingCallbacks,
            $this->resolvingCallbacks
        );
        $this->fireResolvingByData(
            $abstract,
            $object,
            $this->globalAfterResolvingCallbacks,
            $this->afterResolvingCallbacks
        );
    }

    /**
     * @param string $abstract
     * @param        $object
     * @param array  $global_callbacks
     * @param array  $types_callbacks
     *
     * @return void
     */
    protected function fireResolvingByData(
        string $abstract,
        $object,
        array $global_callbacks = [],
        array $types_callbacks = []
    ): void {
        if (!empty($global_callbacks)) {
            $this->fireCallbackArray($object, $global_callbacks);
        }

        $callbacks = $this->getCallbacksForType($abstract, $object, $types_callbacks);
        if (!empty($callbacks)) {
            $this->fireCallbackArray($object, $callbacks);
        }
    }


    /**
     * Fire an array of callbacks with an object.
     *
     * @param mixed $object
     * @param array $callbacks
     *
     * @return void
     */
    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param string $abstract
     * @param mixed  $value
     * @param array  $callbacksPerType
     *
     * @return array
     */
    protected function getCallbacksForType(string $abstract, mixed $value, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $value instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }


}