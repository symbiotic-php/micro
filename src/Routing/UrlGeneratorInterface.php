<?php

declare(strict_types=1);

namespace Symbiotic\Routing;

interface UrlGeneratorInterface
{
    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     */
    public function asset(string $path = '', bool $absolute = true): string;

    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     */
    public function to(string $path = '', bool $absolute = true): string;

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string;

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws RouteNotFoundException
     */
    public function adminRoute(string $name, array $parameters = [], bool $absolute = true): string;

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public function apiRoute(string $name, array $parameters = [], bool $absolute = true): string;
}