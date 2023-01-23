<?php

declare(strict_types=1);

namespace Symbiotic\Container;

use ArgumentCountError;
use Closure;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionParameter;

trait ContainerTrait  /* implements DIContainerInterface */
{
    use ArrayAccessTrait,
        DeepGetterTrait,
        MultipleAccessTrait,
        CommonContainerMethods;

    /**
     * An array of the types that have been resolved.
     *
     * @var bool[]
     */
    protected array $resolved = [];
    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected array $bindings = [];
    /**
     * The container's shared instances.
     *
     * @var object[]
     */
    protected array $instances = [];
    /**
     * The registered type aliases.
     *
     * @var string[]
     */
    protected array $aliases = [];
    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array[]
     *
     * @used-by alias()
     */
    protected array $abstractAliases = [];
    /**
     * The extension closures for services.
     *
     * @var array[]
     */
    protected array $extenders = [];
    /**
     * The stack of concretions currently being built.
     *
     * @var array[]
     */
    protected array $buildStack = [];
    /**
     * The parameter override stack.
     *
     * @var array[]
     */
    protected array $with = [];
    /**
     * @var
     */
    protected mixed $current_build = null;

    /**
     * All of the registered rebound callbacks.
     *
     * @var array[]
     */
    protected array $reboundCallbacks = [];


    /**
     * @var DIContainerInterface[]
     */
    protected array $containersStack = [];

    /**
     * @var array
     */
    protected array $live = [];


    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->bound($key);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
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
        return isset($this->aliases[$name]);
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

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param string $abstract
     * @param string $concrete
     *
     * @return Closure
     *
     * @todo protected?
     */
    public function getClosure(string $abstract, string $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            /**
             * @var DIContainerInterface $container
             */
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }
            return $container->resolve(
                $concrete,
                $parameters,
                false
            );
        };
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
        $abstract = $this->getAlias($abstract);

        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]);
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     *
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        while (isset($this->aliases[$abstract])) {
            $abstract = $this->aliases[$abstract];
        }
        return $abstract;
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     *
     * @return void
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach (($this->reboundCallbacks[$abstract] ?? []) as $callback) {
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array  $parameters
     *
     * @return mixed
     * @throws
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array  $parameters
     * @param bool   $raiseEvents
     *
     * @return mixed
     *
     * @throws Exception|BindingResolutionException|NotFoundException
     */
    public function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        if (empty($parameters)) {
            $container = !empty($this->containersStack) ? end($this->containersStack) : null;
            if ($container instanceof $abstract) {
                return $container;
            }
        }

        $abstract = $this->getAlias($abstract);
        $interface = DIContainerInterface::class;
        if (isset($parameters[$interface])) {
            if ($abstract === $interface) {
                return $parameters[$interface];
            }
            $this->containersStack[] = $parameters[$interface];
            unset($parameters[$interface]);
        } else {
            $this->containersStack[] = $this;
        }

        $conceptual_concrete = $this->current_build ? $this->getContextualConcrete(
            $this->current_build,
            $abstract
        ) : null;


        $needsContextualBuild = !empty($parameters) || null !== $conceptual_concrete;

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (!$needsContextualBuild) {
            if (isset($this->instances[$abstract])) {
                \array_pop($this->containersStack);
                return $this->instances[$abstract];
            }
        }

        $this->with[] = $parameters;
        $alias_abstr = $this->getAlias($abstract);
        $concrete = !empty($conceptual_concrete) ?
            $conceptual_concrete :
            (isset($this->bindings[$abstract])
                ? $this->bindings[$abstract]['concrete'] :
                ((($this instanceof ServiceContainerInterface
                        && $this->loadDefer($abstract))
                    && isset($this->bindings[$alias_abstr])
                ) ? $this->bindings[$alias_abstr]['concrete'] :
                    $abstract)
            );


        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract)) {
            if (\is_string($concrete) && !str_contains($concrete, '\\')) {
                \array_pop($this->containersStack);
                throw new NotFoundException($concrete, $this);
            }
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && !$needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        $this->resolved[$abstract] = true;

        \array_pop($this->with);
        \array_pop($this->containersStack);

        return $object;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     *
     * @param string $for_building
     * @param string $need The name of the class ('\MySpace\ClassName') or variable ('$var_name') to build the
     *                     dependency on.
     *
     * @return Closure|mixed|null
     */
    protected function getContextualConcrete(string $for_building, string $need): mixed
    {
        $current_container = end($this->containersStack);
        return ($current_container instanceof ContextualBindingsInterface) ? $current_container->getContextualConcrete(
            $for_building,
            $need
        ) : null;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param string|Closure $concrete
     * @param string         $abstract
     *
     * @return bool
     */
    protected function isBuildable(string|Closure $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param Closure|string $concrete
     * @param array |null    $params
     *
     * @return mixed
     *
     * @throws BindingResolutionException|ContainerException|\ReflectionException
     */
    public function build(string|Closure $concrete, array $params = null): mixed
    {
        if (null !== $params) {
            $this->with[] = $params;
        }
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Exception $e) {
            throw new ContainerException(
                "Target [$concrete] is not instantiable and key not exists in container data!",
                3243,
                $e
            );
        }


        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            if (!empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);
                $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }
            throw new ContainerException($message);
        }

        $this->buildStack[] = $concrete;
        $this->current_build = $concrete;
        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (null === $constructor) {
            array_pop($this->buildStack);
            array_pop($this->with);
            $this->current_build = end($this->buildStack);
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $instances = $this->resolveDependencies(
            $dependencies
        );

        array_pop($this->buildStack);
        array_pop($this->with);
        $this->current_build = end($this->buildStack);
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Get the last parameter override.
     *
     * @return array
     */
    protected function getLastParameterOverride(): array
    {
        return !empty($this->with) ? end($this->with) : [];
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array|ReflectionParameter[] $dependencies
     *
     * @return array
     *
     * @throws BindingResolutionException|\ReflectionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $k => $dependency) {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency, $k)) {
                $results[] = $this->getParameterOverride($dependency, $k);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $results[] = is_null(Reflection::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Determine if the given dependency has a parameter override.
     *
     * @param ReflectionParameter $dependency
     * @param int|null            $param_number
     *
     * @return bool
     */
    protected function hasParameterOverride(ReflectionParameter $dependency, int $param_number = null): bool
    {
        $params = $this->getLastParameterOverride();
        return (array_key_exists($dependency->name, $params)
            || (null !== $param_number && array_key_exists($param_number, $params)));
    }

    /**
     * Get a parameter override for a dependency.
     *
     * @param ReflectionParameter $dependency
     * @param int|null            $param_number
     *
     * @return mixed
     */
    protected function getParameterOverride(ReflectionParameter $dependency, int $param_number = null): mixed
    {
        $params = $this->getLastParameterOverride();
        if (array_key_exists($dependency->name, $params)) {
            return $params[$dependency->name];
        } elseif (null !== $param_number && array_key_exists($param_number, $params)) {
            return $params[$param_number];
        } elseif (($class = Reflection::getParameterClassName($dependency)) && array_key_exists($class, $params)) {
            return $params[$class];
        } /*elseif (null !== $param_number && array_key_exists($param_number, $value_params)) {
            return $value_params[$param_number];
        }*/
        return null;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($this->current_build && !is_null(
                $concrete = $this->getContextualConcrete($this->current_build, '$' . $parameter->name)
            )) {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new ArgumentCountError(
            "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}::{$parameter->getDeclaringFunction()->getName()}"
        );
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter
     *
     * @return object|null|mixed
     *
     * @throws Exception|BindingResolutionException|\ReflectionException
     */
    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        try {
            $container = end($this->containersStack);
            $class = Reflection::getParameterClassName($parameter);
            return $container ? $container->make($class) : $this->make($class);
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
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
        $container = !empty($this->containersStack) ? end($this->containersStack) : null;
        // If the current container is not ours, then we pass it to him for wrapping
        return $container instanceof $this
            ? $this->extenders[$this->getAlias($abstract)] ?? []
            : $container->getExtenders($abstract);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || ($this->bindings[$abstract]['shared'] ?? false) === true;
    }

    public function delete(string|int $key): bool
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
        return true;
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
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
     * Register a shared binding in the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return static
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
     * Register a request live binding in the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return static
     */
    public function live(string $abstract, Closure|string $concrete = null, string $alias = null): static
    {
        $this->live[$abstract] = true;
        $this->delete($abstract);// If there is a new request, then we delete the old binding from the cloned container
        return $this->singleton($abstract, $concrete, $alias);
    }

    /**
     * @param string $abstract
     *
     * @return $this
     */
    public function setLive(string $abstract): static
    {
        $this->live[$abstract] = true;
        return $this;
    }

    /**
     * @return void
     */
    public function clearLive(): void
    {
        foreach (array_keys($this->live) as $class) {
            $alias = $this->getAlias($class);

            unset($this->instances[$alias], $this->resolved[$alias]);
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
            throw new LogicException("[$abstract] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][$alias] = $alias;
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
     * Refresh an instance on the given target and method.
     *
     * @param string $abstract
     * @param object $target
     * @param string $method
     *
     * @return mixed
     */
    public function refresh(string $abstract, object $target, string $method): mixed
    {
        return $this->rebinding(
            $abstract,
            function ($app, $instance) use ($target, $method) {
                $target->{$method}($instance);
            }
        );
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * @param string  $abstract
     * @param Closure $callback
     *
     * @return mixed
     */
    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
        return null;
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param Closure     $callback
     * @param array       $parameters
     * @param string|null $defaultMethod
     *
     * @return Closure
     */
    public function wrap(Closure $callback, array $parameters = [], ?string $defaultMethod = null): Closure
    {
        return function () use ($callback, $parameters, $defaultMethod) {
            return $this->call($callback, $parameters, $defaultMethod);
        };
    }

    /**
     * Call the given Closure | 'class@method' and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function call(callable|string $callback, array $parameters = [], string $defaultMethod = null): mixed
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
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
     * @param DIContainerInterface $container
     *
     * @return void
     */
    public function setContainersStack(DIContainerInterface $container): void
    {
        $this->containersStack[] = $container;
    }

    /**
     * @return void
     */
    public function popCurrentContainer(): void
    {
        array_pop($this->containersStack);
    }

    /**
     * Remove all of the extender callbacks for a given type.
     *
     * @param string $abstract
     *
     * @return void
     */
    public
    function forgetExtenders(
        string $abstract
    ): void {
        unset($this->extenders[$this->getAlias($abstract)]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param string $abstract
     *
     * @return void
     */
    public
    function forgetInstance(
        string $abstract
    ): void {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * @return void
     */
    public
    function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public
    function clear(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
        $this->live = [];
    }
}