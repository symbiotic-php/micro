<?php

declare(strict_types=1);

namespace Symbiotic\Container;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * Interface DependencyInjectionInterface
 * @package Symbiotic\Container
 */
interface DIContainerInterface extends ArrayContainerInterface, ContainerInterface, FactoryInterface
{

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function alias(string $abstract, string $alias): void;


    /**
     * Register a binding with the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param bool                $shared
     *
     * @return void
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void;

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * @param string  $abstract
     * @param Closure $callback
     *
     * @return mixed
     */
    public function rebinding(string $abstract, Closure $callback): mixed;

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param bool                $shared
     *
     * @return $this
     */
    public function bindIf(string $abstract, Closure|string $concrete = null, bool $shared = false): static;

    /**
     * Register a shared binding in the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return static
     */
    public function singleton(string $abstract, Closure|string $concrete = null, string $alias = null): static;


    /**
     * Register a request live binding in the container.
     *
     * @param string              $abstract
     * @param Closure|string|null $concrete
     * @param string|null         $alias
     *
     * @return static
     */
    public function live(string $abstract, Closure|string $concrete = null, string $alias = null): static;


    /**
     * @return void
     */
    public function clearLive(): void;

    /**
     * "Extend" an abstract type in the container.
     *
     * @param string  $abstract
     * @param Closure $closure
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend(string $abstract, Closure $closure): void;

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string      $abstract
     * @param mixed       $instance
     * @param null|string $alias
     *
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance, string $alias = null): mixed;

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array  $parameters
     * @param bool   $raiseEvents
     *
     * @return mixed
     *
     * @throws BindingResolutionException;
     */
    public function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true): mixed;

    /**
     * @param Closure|string $concrete string class name or closure factory
     *
     * @return mixed
     * @example function(DependencyInjectionInterface $container, array $params = []){.... return $object;}
     */
    public function build(string|Closure $concrete, array $params = null): mixed;

    /**
     * @return array
     */
    public function getBindings():array;

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param string $abstract
     *
     * @return Closure
     */
    public function factory(string $abstract): Closure;

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Call the given Closure or 'className@methodName' and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call(callable|string $callback, array $parameters = [], string $defaultMethod = null): mixed;

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     *
     * @return bool
     */
    public function resolved(string $abstract): bool;

    /**
     * Register a new resolving callback.
     *
     * @param Closure|string $abstract if closure set to global resolving event
     * @param callable|null  $callback
     *
     * @return void
     */
    public function resolving(string|Closure $abstract, callable $callback = null): void;

    /**
     * Register a new after resolving callback.
     *
     * @param Closure|string $abstract
     * @param callable|null  $callback
     *
     * @return void
     */
    public function afterResolving(string|Closure $abstract, callable $callback = null): void;

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isAlias(string $name): bool;

    /**
     * Get aliases for abstract binding
     *
     * @param string $abstract
     *
     * @return array|null
     */
    public function getAbstractAliases(string $abstract): ?array;

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     *
     * @return string
     */
    public function getAlias(string $abstract): string;


    /**
     * Special get method with default
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return mixed
     */
    public function __invoke(string $key, mixed $default = null): mixed;
}
