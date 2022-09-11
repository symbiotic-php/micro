<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


/**
 * Class Router
 * @package Symbiotic\Routing
 *
 */
interface RouterFactoryInterface
{
    /**
     * @param array $params
     *
     * @return RouterInterface
     */
    public function factoryRouter(array $params = []): RouterInterface;

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    public function loadRoutes(RouterInterface $router): void;
}
