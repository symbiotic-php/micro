<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\AbstractBootstrap;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\Events\CacheClear;


class Bootstrap extends AbstractBootstrap
{
    public function bootstrap(DIContainerInterface $core): void
    {
        /**
         * Cache Manager
         */
        $core->singleton(
            CacheManagerInterface::class,
            static function (DIContainerInterface $app) {
                return new CacheManager($app);
            },
            'cache'
        );

        /**
         * Psr simple cache store
         */
        $interface = \Psr\SimpleCache\CacheInterface::class;
        $core->singleton(
            $interface,
            static function (CoreInterface $app) {
                return $app->get(CacheManagerInterface::class)->store();
            },
            'cache_store'
        )->alias($interface, CacheInterface::class);

        /**
         * We subscribe to the cache clearing event,
         * if a total cleanup is needed, then we clear the entire storage
         */
        $core['listeners']->add(CacheClear::class, static function (CacheClear $event, CoreInterface $core) {
            if ($event->getPath() === 'all') {
                $core[CacheInterface::class]->clear();
            }
        });
    }
}
