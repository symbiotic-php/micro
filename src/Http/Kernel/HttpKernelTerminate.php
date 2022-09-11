<?php
declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class HttpKernelTerminate
{

    public function __construct(
        public ContainerInterface $container,
        public ServerRequestInterface $request,
        public ?ResponseInterface $response
    ) {
    }
}