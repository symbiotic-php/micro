<?php

declare(strict_types=1);

namespace Symbiotic\Routing;

/**
 * Interface RouteInterface
 * @package Symbiotic\Routing
 */
interface RouteInterface
{
    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @return array
     */
    public function getAction(): array;

    /**
     * @return bool
     */
    public function isStatic(): bool;

    /**
     * Middleware Array
     *
     * @return array|string[]|\Closure[]
     *
     * @see Router::addRoute()
     * You can add \Closure to the array
     *      function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface{}
     */
    public function getMiddlewares(): array;

    /**
     * Http scheme
     * true https
     * false http
     * @return bool
     */
    public function getSecure(): bool;

    /**
     * @return string|null domain name (www.example.com)
     */
    public function getDomain(): ?string;

    /**
     * @param string $domain
     *
     * @return static
     */
    public function setDomain(string $domain): static;


    /**
     * @return callable|string|null
     */
    public function getHandler(): mixed;

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setParam(string $key, mixed $value): void;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getParam(string $key): mixed;

    /**
     * @return array
     */
    public function getParams(): array;
}
