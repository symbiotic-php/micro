<?php

declare(strict_types=1);

namespace Symbiotic\Routing;

use Symbiotic\Container\DIContainerInterface;

/**
 * Class RouterFactory
 * @package Symbiotic\Routing
 */
class RouterFactory implements RouterFactoryInterface
{

    public function __construct(
        protected DIContainerInterface $app,
        protected string $router_class,
        protected \Closure $routes_loader_callback,
        protected array $params = []
    ) {
    }

    /**
     * @param array $params
     *
     * @return RouterInterface
     */
    public function factoryRouter(array $params = []): RouterInterface
    {
        /** @var RouterInterface $router */
        $router = new $this->router_class();
        $router->setParams(array_merge($this->params, $params));

        return $router;
    }

    public function loadRoutes(RouterInterface $router): void
    {
        $callable = $this->routes_loader_callback;
        $callable($router);
    }

}
