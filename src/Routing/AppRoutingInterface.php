<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


interface AppRoutingInterface
{

    /**
     * @return string
     */
    public function getAppId(): string;

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadBackendRoutes(RouterInterface $router): void;

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadApiRoutes(RouterInterface $router): void;

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadFrontendRoutes(RouterInterface $router): void;

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadDefaultRoutes(RouterInterface $router): void;
}