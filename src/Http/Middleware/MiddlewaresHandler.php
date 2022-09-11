<?php

declare(strict_types=1);

namespace Symbiotic\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class MiddlewareHandler
 * @package  Symbiotic\Http\Middleware
 * @category Symbiotic\Http
 *
 * @author   shadowhand https://github.com/shadowhand
 * @link     https://github.com/jbboehr/dispatch - base source
 */
class MiddlewaresHandler implements RequestHandlerInterface
{
    use MiddlewaresCollectionTrait;

    /**
     * @var MiddlewareInterface[]
     */
    protected array $middleware = [];

    /**
     * @var RequestHandlerInterface
     */
    protected RequestHandlerInterface $handler;

    /**
     * @var int
     */
    protected int $index = 0;

    /**
     * @param MiddlewareInterface[]   $middleware
     * @param RequestHandlerInterface $handler
     */
    public function __construct(RequestHandlerInterface $handler, array $middleware = [])
    {
        $this->handler = $handler;
        $this->middleware = $middleware;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRealHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middleware)) {
            return $this->handler->handle($request);
        }
        $middleware = \array_shift($this->middleware);
        return $middleware->process($request, clone $this);
    }
}