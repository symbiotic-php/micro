<?php

declare(strict_types=1);

namespace Symbiotic\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;


trait MiddlewaresCollectionTrait
{
    /**
     * @var MiddlewareInterface[]
     */
    protected array $middleware = [];

    /**
     * Add a middleware to the end of the stack.
     *
     * @param MiddlewareInterface $middleware
     *
     * @return void
     */
    public function append(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Add a middleware to the beginning of the stack.
     *
     * @param MiddlewareInterface $middleware
     *
     * @return void
     */
    public function prepend(MiddlewareInterface $middleware): void
    {
        \array_unshift($this->middleware, $middleware);
    }

}