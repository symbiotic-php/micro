<?php

declare(strict_types=1);

namespace Symbiotic\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Symbiotic\Container\CloningContainer;
use Symbiotic\Container\DIContainerInterface;

/**
 * Class MiddlewaresDispatcher
 * @package  Symbiotic\Http\Middleware
 * @category Symbiotic\Http
 *
 * @notice   The use of functions as middleware is made exclusively for Micro package
 * @example  function(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {}
 */
class MiddlewaresDispatcher implements CloningContainer
{

    const GROUP_GLOBAL = 'global';
    /**
     * @var string[][]|\Closure[][]|array = [
     *     'name' => [Middlewares ...],
     *     ...
     * ]
     */
    protected array $middlewares_groups = [
        self::GROUP_GLOBAL => []
    ];

    /**
     * @var array |\Closure[]
     */
    protected array $binds = [];

    /**
     * @uses
     * @used-by factory()
     * @var null|\Closure
     */
    protected ?\Closure $default_factory;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(protected ContainerInterface $container)
    {
        $this->default_factory = static::createDefaultFactory($this->container);
    }

    /**
     * @param string $name
     * @param array  $middlewares
     *
     * @return void
     */
    public function addMiddlewareGroup(string $name, array $middlewares): void
    {
        $this->middlewares_groups[$name] = $middlewares;
    }

    /**
     * @param string        $name
     * @param string        $middleware Class name
     * @param \Closure|null $bind
     *
     * @return $this
     */
    public function appendToGroup(string $name, string $middleware, \Closure $bind = null): self
    {
        if (!isset($this->middlewares_groups[$name])) {
            $this->middlewares_groups[$name] = [];
        }
        $this->middlewares_groups[$name][] = $middleware;
        if ($bind) {
            $this->bind($middleware, $bind);
        }

        return $this;
    }

    /**
     * @param string   $middleware_classname
     * @param \Closure $callback
     */
    public function bind(string $middleware_classname, \Closure $callback)
    {
        $this->binds[$middleware_classname] = $callback;
    }

    /**
     * @param string        $name
     * @param string        $middleware
     * @param \Closure|null $bind
     *
     * @return $this
     */
    public function prependToGroup(string $name, string $middleware, \Closure $bind = null): static
    {
        if (!isset($this->middlewares_groups[$name])) {
            $this->middlewares_groups[$name] = [];
        }
        array_unshift($this->middlewares_groups[$name], $middleware);
        if ($bind) {
            $this->bind($middleware, $bind);
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return array|MiddlewareInterface[]
     * @throws \Exception
     */
    public function factoryGroup(string $name): array
    {
        $middlewares = $this->getMiddlewareGroup($name);
        return $this->factoryCollection($middlewares);
    }

    /**
     * @param string $name
     *
     * @return \Closure[]|string[]
     * @throws \Exception
     */
    public function getMiddlewareGroup(string $name): array
    {
        if (!isset($this->middlewares_groups[$name])) {
            throw new MiddlewareException(
                'Middleware group [' . htmlspecialchars($name) . '] not found'
            ); //Группа промежуточного программного обеспечения не найдена
        }
        return $this->middlewares_groups[$name];
    }

    /**
     * @param array $middlewares
     *
     * @return array|MiddlewareInterface[]
     */
    public function factoryCollection(array $middlewares):array
    {
        return array_map(function ($v) {
            return $this->factory($v);
        }, $middlewares);
    }

    /**
     * @param string|\Closure $middleware
     *
     * @return MiddlewareInterface
     */
    public function factory(string|\Closure $middleware): MiddlewareInterface
    {
        if ($middleware instanceof \Closure) {
            return new MiddlewareCallback($middleware);
        }
        if (isset($this->middlewares_groups[$middleware])) {
            $middlewares = $this->factoryCollection($this->middlewares_groups[$middleware]);
            return new MiddlewaresCollection($middlewares);
        }
        if (!class_exists($middleware)) {
            throw new MiddlewareException('Middleware group or class [' . $middleware . '] not found!');
        }
        $factory = $this->binds[$middleware] ?? (
            $this->default_factory ?:
                function ($class) {
                    return new $class();
                }
            );
        return $factory($middleware);
    }

    public static function createDefaultFactory(ContainerInterface $container): \Closure
    {
        return function ($class) use ($container) {
            /**
             * @var ContainerInterface|DIContainerInterface $container
             */
            return $container->get($class);
        };
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return $this|null
     */
    public function cloneInstance(?ContainerInterface $container): ?static
    {
        $new = clone $this;
        $new->default_factory = static::createDefaultFactory($container);

        return $new;
    }


}