<?php

declare(strict_types=1);

namespace Symbiotic\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Container\CloningContainer;
use Symbiotic\Http\Kernel\HttpRunner;

interface HttpKernelInterface extends RequestHandlerInterface, CloningContainer
{
    /**
     * @return void
     */
    public function bootstrap(): void;

    /**
     * @param int              $code
     * @param \Throwable |null $exception
     *
     * @return ResponseInterface
     */
    public function response(int $code = 200, \Throwable $exception = null): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ?ResponseInterface     $response null if is not handled request {@see HttpRunner::run()}
     *
     * @return void
     * @uses \Symbiotic\Http\Kernel\HttpKernelTerminate
     */
    public function terminate(ServerRequestInterface $request, ?ResponseInterface $response): void;
}
