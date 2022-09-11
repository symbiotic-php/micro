<?php

declare(strict_types=1);

namespace Symbiotic\Auth;

use Psr\Http\Message\ResponseFactoryInterface;
use Symbiotic\Auth\Authenticator\MultiAuthenticator;
use Symbiotic\Auth\Storage\AuthSessionStorage;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\ServiceProvider;
use Symbiotic\Event\ListenersInterface;
use Symbiotic\Http\Kernel\RouteMiddlewares;
use Symbiotic\Routing\RouteInterface;
use Symbiotic\Session\SessionStorageInterface;


class Provider extends ServiceProvider
{
    public function register(): void
    {
        /**
         * Auth service
         */
        $this->app->live(
            AuthServiceInterface::class,
            static function (DIContainerInterface $app) {
                return new AuthService($app[AuthStorageInterface::class], $app[MultiAuthenticator::class]);
            },
            'auth'
        );

        /**
         * Auth storage
         */
        $this->app->bind(
            AuthStorageInterface::class,
            static function (DIContainerInterface $app) {
                return new AuthSessionStorage($app[SessionStorageInterface::class]);
            }
        );

        $this->app->live(MultiAuthenticator::class);

        /**
         * Before performing the rout, we check the user's access
         */
        $this->app->get(ListenersInterface::class)
            ->add(
                RouteMiddlewares::class,
                static function (RouteMiddlewares $event, CoreInterface $app) {
                    $event->prepend(
                        new AuthMiddleware($app, $app[RouteInterface::class], $app[ResponseFactoryInterface::class])
                    );
                }
            );
    }

}