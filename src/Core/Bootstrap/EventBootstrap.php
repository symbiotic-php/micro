<?php

declare(strict_types=1);

namespace Symbiotic\Core\Bootstrap;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;
use Symbiotic\Event\EventDispatcher;
use Symbiotic\Event\ListenerProvider;
use Symbiotic\Event\ListenersInterface;


class EventBootstrap implements BootstrapInterface
{
    public function bootstrap(DIContainerInterface $core): void
    {
        // Events listeners
        $listener_interface = ListenerProviderInterface::class;
        $core->singleton($listener_interface, static function ($app) {
            return new ListenerProvider(static::getListenerWrapper($app));
        },               'listeners')
            ->alias($listener_interface, ListenersInterface::class);

        // Events dispatcher
        $core->live(EventDispatcherInterface::class, static function($app) {
            return new EventDispatcher(
                $app->get(ListenerProviderInterface::class)
            );
            }, 'events');
    }

    public static function getListenerWrapper(DIContainerInterface $app)
    {
        return static function ($listener) use ($app) {
            return static function (object $event) use ($listener, $app) {
                if (is_string($listener) && class_exists($listener)) {
                    $handler = $app->make($listener);
                    if (method_exists($handler, 'handle') || is_callable($handler)) {
                        return $app->call([$handler, method_exists($handler, 'handle') ? 'handle' : '__invoke'],
                                          ['event' => $event]);
                    }
                    return null;
                } elseif ($listener instanceof \Closure) {
                    return $app->call($listener, ['event' => $event]);
                }
            };
        };
    }
}
