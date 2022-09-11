<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

use Symbiotic\Apps\ApplicationInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;
use Symbiotic\Core\Config;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Filesystem\FilesystemManagerInterface;

use function _S\collect;
use function _S\settings;

class SettingsBootstrap implements BootstrapInterface
{
    public function bootstrap(DIContainerInterface $core): void
    {
        /**
         * Settings Storage
         * You can change it by linking your storage earlier via bootstrap
         */
        if (!$core->bound(SettingsStorageInterface::class)) {
            $core->bind(SettingsStorageInterface::class, static function ($core) {
                return new SettingsStorageFilesystem(rtrim($core['storage_path'], '/\\') . '/settings');
            });
        }

        /**
         *  Settings repository
         */
        $core->singleton(SettingsRepositoryInterface::class, SettingsRepository::class);

        /**
         * Binding for creation via the kernel {@used-by \_S\settings()}
         */
        $core->bind(SettingsInterface::class, Settings::class);

        /**
         * Bind Settings to Application
         *
         * After creating the application class, we bind the settings class to it,
         * to everyone in a row, even if there are no settings
         */
        $core->afterResolving(ApplicationInterface::class, function (ApplicationInterface $application) {
            $application->alias(SettingsInterface::class, Settings::class);
            $application->singleton(SettingsInterface::class, function ($app) {
                return settings($app->get(CoreInterface::class), $app->getId());
            });
        });


        /**
         * To get the package settings without an application in the form of an array,
         * you can directly access the repository
         *
         *   $repository = $core[SettingsRepositoryInterface::class];
         *   $package_settings = $repository->get($package_id);
         *
         * @see \_S\settings()
         */
        /**
         * TODO: This should be done after bootstrap!
         * This is necessary for the correct choice of storage, it can later be bound to the kernel by a plugin {@see SettingsStorageInterface}
         */
        $config = $core['config'];
        if ($core[SettingsRepositoryInterface::class]->has('core')) {
            /**
             * @var SettingsInterface $core_settings
             * @var Config            $config
             */
            $core_settings = collect($core[SettingsRepositoryInterface::class]->get('core'));

            if ($core_settings->has('uri_prefix')) {
                $config->set('uri_prefix', $core_settings['uri_prefix']);
            }
            foreach (['default_host', 'backend_prefix', 'assets_prefix'] as $v) {
                if (!empty($core_settings[$v])) {
                    $config->set($v, $core_settings[$v]);
                }
            }

            foreach (['debug', 'packages_settlements', 'symbiosis'] as $v) {
                if ($core_settings->has($v)) {
                    $config->set($v, (bool)$core_settings[$v]);
                }
            }

            $core->instance(SettingsInterface::class, $core[SettingsRepositoryInterface::class]->get('core'));
        }


        /**
         * Append filesystem disks from settings
         */
        if ($core[SettingsRepositoryInterface::class]->has('filesystems')) {
            /**
             * @var SettingsInterface $core_settings
             * @var Config            $config
             */
            $filesystems_settings = $core[SettingsRepositoryInterface::class]->get('filesystems');
            if (!empty($filesystems_settings) && is_array($filesystems_settings)) {
                $core->afterResolving(
                    FilesystemManagerInterface::class,
                    function (FilesystemManagerInterface $manager) use ($filesystems_settings, $config) {
                        $filesystems = $config->get('filesystems', []);
                        if (!isset($filesystems['disks'])) {
                            $filesystems['disks'] = [];
                        }
                        $filesystems['disks'] = array_merge($filesystems['disks'], $filesystems_settings);
                        $config->set('filesystems', $filesystems);
                    }
                );
            }
        }
    }
}