<?php
declare(strict_types=1);

namespace Symbiotic\Http;


use Symbiotic\Core\{CoreInterface,BootstrapInterface};

use Psr\Http\Message\{StreamFactoryInterface,
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface};
use Symbiotic\Container\DIContainerInterface;


class Bootstrap implements BootstrapInterface
{
    public function bootstrap(DIContainerInterface $core): void
    {
        $concrete = PsrHttpFactory::class;
        $core->singleton($concrete);
        $core->alias($concrete, 'http_factory');
        $core->alias($concrete, UriFactoryInterface::class);
        $core->alias($concrete, StreamFactoryInterface::class);
        $core->alias($concrete, ResponseFactoryInterface::class);
        $core->alias($concrete, ServerRequestFactoryInterface::class);
        $core->alias($concrete, RequestFactoryInterface::class);
        $core->alias($concrete, UploadedFileFactoryInterface::class);

    }
}