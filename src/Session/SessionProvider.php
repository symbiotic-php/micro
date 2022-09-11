<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Psr\Container\ContainerInterface;
use Symbiotic\Core\ServiceProvider;
use Symbiotic\Event\ListenersInterface;
use Symbiotic\Http\Middleware\MiddlewaresDispatcher;


class SessionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->live(SessionManagerInterface::class, SessionManager::class, 'session_manager');

        $this->app->live(
            SessionStorageInterface::class,
            static function (ContainerInterface $app) {
                return $app->get(SessionManagerInterface::class)->store();
            },
            'session'
        );

        // add session middleware
        $this->app->get(ListenersInterface::class)->add(
            MiddlewaresDispatcher::class,
            static function (MiddlewaresDispatcher $dispatcher) {
                $dispatcher->appendToGroup(MiddlewaresDispatcher::GROUP_GLOBAL, SessionMiddleware::class);
            }
        );
    }
}