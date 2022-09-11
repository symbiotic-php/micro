<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


class AppRouting implements AppRoutingInterface
{
    /**
     * @see \Symbiotic\Packages\PackagesRepository::addPackage()
     */
    protected string $app_id;

    /**
     * @var string|null
     */
    protected ?string $controllers_namespace = null;


    public function __construct(string $app_id, string $controllers_namespace = null)
    {
        $this->app_id = $app_id;
        $this->controllers_namespace = $controllers_namespace;
    }

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function backendRoutes(RouterInterface $router): void
    {
    }

    public function frontendRoutes(RouterInterface $router): void
    {
    }

    public function apiRoutes(RouterInterface $router): void
    {
    }

    public function defaultRoutes(RouterInterface $router): void
    {
    }


    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadBackendRoutes(RouterInterface $router): void
    {
        $options = $this->getRoutingOptions();
        unset($options['prefix']);
        unset($options['as']);
        $router->group(
            $options,
            $this->getLoadRoutesCallback('backendRoutes')
        );
    }


    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadApiRoutes(RouterInterface $router): void
    {
        $options = $this->getRoutingOptions();
        unset($options['prefix']);
        unset($options['as']);
        $router->group(
            $options,
            $this->getLoadRoutesCallback('apiRoutes')
        );
    }

    public function loadFrontendRoutes(RouterInterface $router): void
    {
        $options = $this->getRoutingOptions();
        unset($options['prefix']);
        unset($options['as']);
        $router->group(
            $options,
            $this->getLoadRoutesCallback('frontendRoutes')
        );
    }

    public function loadDefaultRoutes(RouterInterface $router): void
    {
        $router->group(
            ['namespace' => $this->controllers_namespace],
            $this->getLoadRoutesCallback('defaultRoutes')
        );
    }

    /**
     * @return array
     */
    protected function getRoutingOptions(): array
    {
        $id = $this->app_id;
        return [
            'prefix' => $id,
            'app' => $id,
            'as' => $id,
            'namespace' => $this->controllers_namespace,
        ];
    }

    /**
     * @param RouterInterface $router
     * @param string          $method
     *
     * @return void
     */
    protected function loadPrefixRoutes(RouterInterface $router, string $method): void
    {
        $router->group(
            $this->getRoutingOptions(),
            $this->getLoadRoutesCallback($method)
        );
    }

    /**
     * @param string $method
     *
     * @return \Closure
     */
    protected function getLoadRoutesCallback(string $method): \Closure
    {
        return function (RouterInterface $router) use ($method) {
            $this->{$method}($router);
        };
    }


    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->app_id;
    }
}