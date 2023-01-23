<?php

declare(strict_types=1);

namespace Symbiotic\Container;

use Closure;


trait SubContainerTrait /*  implements DIContainerInterface, ContextualBindingsInterface*/
{
    use DeepGetterTrait,
        ArrayAccessTrait,
        MethodBindingsTrait,
        ContextualBindingsTrait,
        CommonContainerMethods;

    /**
     * @var DIContainerInterface|null
     */
    protected ?DIContainerInterface $app = null;

    /**
     * @var array
     */
    protected array $aliases = [];

    /**
     * @var array
     */
    protected array $instances = [];

    /**
     * @var array
     */
    protected array $abstractAliases = [];

    /**
     * @var array
     */
    protected array $reboundCallbacks = [];

    /**
     * @var array
     */
    protected array $bindings = [];

    /**
     * @var array
     */
    protected array $resolved = [];

    /**
     * @var array
     */
    protected array $extenders = [];

    /**
     * @var array
     */
    protected array $live = [];


    /**
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function call(callable|string $callback, array $parameters = [], string $defaultMethod = null): mixed
    {
        return BoundMethod::call($this, $callback, $this->bindParameters($parameters), $defaultMethod);
    }

    /**
     * @param array $parameters
     *
     * @return array
     * @used-by resolve()
     * @used-by call()
     */
    public function bindParameters(array &$parameters): array
    {
        $di = DIContainerInterface::class;
        if (!isset($parameters[$di])) {
            $parameters[$di] = $this;
        }

        return $parameters;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @todo: нужно тестировать правильность работы с родительским
     */
    public function has(string $key): bool
    {
        return isset($this->bindings[$key])
            || isset($this->instances[$key])
            || isset($this->aliases[$key])
            || $this->app->has($this->getAlias($key));
    }

    /**
     * @param string $abstract
     *
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (!isset($this->aliases[$abstract])) {
            return $this->app->getAlias($abstract);
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->bind(
            $key,
            $value instanceof Closure ? $value : function () use ($value) {
                return $value;
            }
        );
    }

    /**
     * Register a binding with the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param bool                $shared
     *
     * @return void
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
        if (!$concrete) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => function ($container, $parameters = []) use ($abstract, $concrete, $shared) {
                /**
                 * @var Container $container
                 */
                if ($concrete instanceof Closure) {
                    $instance = $concrete($this, $parameters);
                    foreach ($this->getExtenders($abstract) as $v) {
                        $instance = $v($instance);
                    }
                    $this->fireResolvingCallbacks($abstract,$instance);
                } else {
                    if ($abstract === $concrete) {
                        if (empty($parameters) && isset($this->instances[$abstract])) {
                            return $this->instances[$abstract];
                        }
                        $container->setContainersStack($this);
                        $instance = $container->build($concrete, empty($parameters) ? null : $parameters);
                        $container->popCurrentContainer();
                        $this->fireResolvingCallbacks($abstract, $instance);
                    } else {
                        $instance = $this->app->resolve(
                            $concrete,
                            $parameters,
                            false
                        );
                    }
                }
                $this->resolved[$abstract] = true;
                if ($shared) {
                    $this->instances[$abstract] = $instance;
                }
                return $instance;
            },
            'shared' => $shared
        ];

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        $alias = $this->getAlias($abstract);
        if (isset($this->resolved[$alias]) || isset($this->instances[$alias])) {
            $this->rebound($abstract);
        }
    }


    /**
     * Get the extender callbacks for a given type.
     *
     * @param string $abstract
     *
     * @return array
     */
    public function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     *
     * @return void
     * @throws
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach (($this->reboundCallbacks[$abstract] ?? []) as $callback) {
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * @param string $abstract
     * @param array  $parameters
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * @param string $abstract
     * @param array  $parameters
     * @param bool   $raiseEvents
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $alias = $this->getAlias($abstract);
        // first we get the key from us
        // otherwise it will return from the kernel by alias
        // the problem of services of the same name with aliases
        if (!$parameters && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (isset($this->bindings[$abstract])) {
            return $this->app->build($this->bindings[$abstract]['concrete'], $parameters);
        }
        // передаем родителю
        if (!$parameters && isset($this->instances[$alias])) {
            return $this->instances[$alias];
        }
        if (isset($this->bindings[$alias])) {
            return $this->app->build($this->bindings[$alias]['concrete']);
        }

        return $this->app->resolve($alias, $this->bindParameters($parameters), $raiseEvents);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key], $this->aliases[$key], $this->abstractAliases[$key]);
        return true;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * @param string  $abstract
     * @param Closure $callback
     *
     * @return mixed
     * @throws
     */
    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
        return null;
    }

    public function bound($abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || isset($this->aliases[$abstract])
            || $this->app->bound($abstract);
    }

    /**
     * @param string|Closure $concrete
     * @param array|null     $params
     *
     * @return mixed
     */
    public function build(string|Closure $concrete, array $params = null): mixed
    {
        return $this->app->build($concrete, $params);
    }

    /**
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param bool                $shared
     *
     * @return $this
     */
    public function bindIf(string $abstract, Closure|string $concrete = null, bool $shared = false): static
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
        return $this;
    }

    /**
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return $this
     */
    public function singleton(string $abstract, Closure|string $concrete = null, string $alias = null): static
    {
        $this->bind($abstract, $concrete, true);
        if (is_string($alias)) {
            $this->alias($abstract, $alias);
        }

        return $this;
    }

    /**
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return $this
     */
    public function live(string $abstract, Closure|string $concrete = null, string $alias = null): static
    {
        $this->live[$abstract] = true;
        $this->delete($abstract);// If there is a new request, then we delete the old binding from the cloned container
        return $this->singleton($abstract, $concrete, $alias);
    }

    /**
     * @return void
     */
    public function clearLive(): void
    {
        foreach (array_keys($this->live) as $class) {
            $alias = $this->getAlias($class);
            unset(
                $this->instances[$class],
                $this->instances[$alias],
                $this->resolved[$alias],
                $this->resolved[$class]
            );
        }
    }

    /**
     * @param string $abstract
     * @param string $alias
     *
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new \LogicException("[$abstract] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]) || $this->app->resolved($abstract);
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]) || $this->app->isAlias($name);
    }

    /**
     * Remove all of the extender callbacks for a given type.
     *
     * @param string $abstract
     *
     * @return void
     */
    public function forgetExtenders(string $abstract): void
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }


    /**
     * @param string $concrete
     * @param string $abstract
     * @param        $implementation
     *
     * @todo: you need to do it separately from the parent!
     */
    public function addContextualBinding(string $concrete, string $abstract, $implementation): void
    {
        $this->app->addContextualBinding($concrete, $abstract, $implementation);
    }

    /**
     * @param string|array $concrete
     *
     * @return ContextualBindingBuilder
     * @todo: you need to do it separately from the parent!
     */
    public function when(string|array $concrete): ContextualBindingBuilder
    {
        return $this->app->when($concrete);
    }

    /**
     * @param string $abstract
     *
     * @return Closure
     */
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }

    /**
     * Get aliases for abstract binding
     *
     * @param string $abstract
     *
     * @return array|null
     */
    public function getAbstractAliases(string $abstract): ?array
    {
        return $this->abstractAliases[$abstract] ?? null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        foreach ($this->instances as $k => $instance) {
            if ($instance instanceof CloningContainer
                && !($instance instanceof $this)
                && ($newService = $instance->cloneInstance(
                    $this
                ))) {
                $this->instances[$k] = $newService;
            } elseif ($instance instanceof $this) {
                $this->instances[$k] = $this;
            }
        }
    }
}
