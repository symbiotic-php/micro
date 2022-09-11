<?php
declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Psr\Container\ContainerInterface;
use Symbiotic\Container\CloningContainer;
use Symbiotic\Core\HttpKernelInterface;
use Symbiotic\Http\Middleware\MiddlewaresHandler;


class PreloadKernelHandler extends MiddlewaresHandler implements CloningContainer
{

    public function cloneInstance(?ContainerInterface $container): ?object
    {
        $new = clone $this;
        /**
         * support for only the basic RequestHandler
         */
        $new->handler = $container->get(HttpKernelInterface::class);
        /**
         * clone middleware if container cloning is supported
         */
        $new->middleware = array_map(function ($v) use ($container) {
            return ($v instanceof CloningContainer) ? $v->cloneInstance($container) : $v;
        }, $new->middleware);

        return $new;
    }
}