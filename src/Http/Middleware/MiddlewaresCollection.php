<?php
declare(strict_types=1);

namespace Symbiotic\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Class MiddlewaresCollection
 * @package Symbiotic\Http\MiddlewareHandler
 *
 * @author shadowhand https://github.com/shadowhand
 * @link https://github.com/jbboehr/dispatch - base source
 */
class MiddlewaresCollection implements MiddlewareInterface
{
    use MiddlewaresCollectionTrait;

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(array $middleware = [])
    {
        array_map(function ($v) {
            $this->append($v);
        }, $middleware);
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new MiddlewaresHandler($handler, $this->middleware))->handle($request);
    }
}