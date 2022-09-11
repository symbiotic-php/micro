<?php

declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Symbiotic\Core\{CoreInterface, BootstrapInterface, HttpKernelInterface};
use Psr\EventDispatcher\EventDispatcherInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Http\Middleware\MiddlewaresDispatcher;


class Bootstrap implements BootstrapInterface
{
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->singleton(HttpKernelInterface::class, HttpKernel::class);
        $core->addRunner(new HttpRunner($core));

        /**
         * Through the event, you can add intermediaries before loading the Http core and all providers
         * This is convenient when you need to respond quickly, it is recommended to use it in an emergency
         */
        $core->singleton(PreloadKernelHandler::class, function ($app) {
            return $app[EventDispatcherInterface::class]->dispatch(
                new PreloadKernelHandler($app->get(HttpKernelInterface::class))
            );
        });

        /**
         * Middlewares Dispatcher
         */
        $core->singleton(MiddlewaresDispatcher::class, function ($app) {
            $dispatcher = new MiddlewaresDispatcher($app);
            return $app[EventDispatcherInterface::class]->dispatch($dispatcher);
        });
    }
}