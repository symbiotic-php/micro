<?php

declare(strict_types=1);

namespace Symbiotic\Core\Bootstrap;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;
use Symbiotic\Core\Config;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\Events\CacheClear;
use Symbiotic\View\ViewFactory;
use Symbiotic\Packages\TemplatesRepositoryInterface;

use function _S\is_console;


class CoreBootstrap implements BootstrapInterface
{
    /**
     * @param CoreInterface $core
     */
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->singleton(
            Config::class,
            static function ($app) {
                return new Config($app['bootstrap_config']);
            },
            'config'
        );

        /**
         * @var Config $config
         */
        $config = $core->get('config');

        $core->singleton(ViewFactory::class, static function ($app) {
            return new ViewFactory($app, $app[TemplatesRepositoryInterface::class]);
        },               'view');
        // Env settings
        if (is_console()) {
            $core['env'] = 'console';
        } else {
            $core['env'] = 'web';
        }

        \date_default_timezone_set($config->get('core.timezone', 'UTC'));
        \mb_internal_encoding('UTF-8');


        $storage_path = $config->get('storage_path');
        $cache_path = $config->get('cache_path');

        if ($storage_path) {
            $core['storage_path'] = $storage_path = \rtrim($storage_path, '\\/');
            if (empty($cache_path)) {
                $cache_path = $storage_path . '/cache/';
            }
        }

        if (!empty($cache_path)) {
            $core['cache_path'] = $cache_path;
            $core['cache_path_core'] = rtrim($cache_path, '\\/') . '/core';
        }

        $start_bootstrappers = $config->get('bootstrappers');
        if (\is_array($start_bootstrappers)) {
            foreach ($start_bootstrappers as $class) {
                $core->runBootstrap($class);
            }
        }
        // When cleaning, we set the flag
        $core['listeners']->add(CacheClear::class, function (CacheClear $event) use ($core) {
            if ($event->getPath() === 'all' || $event->getPath() === 'core') {
                $core['cache_cleaned'] = true;
            }
        });
    }
}