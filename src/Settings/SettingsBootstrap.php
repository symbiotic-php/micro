<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

use Symbiotic\Apps\ApplicationInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;
use Symbiotic\Core\Config;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Filesystem\FilesystemException;
use Symbiotic\Filesystem\FilesystemManagerInterface;

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
             * @var Settings $core_settings
             * @var Config            $config
             */
            $core_settings = new Settings($core[SettingsRepositoryInterface::class]->get('core')??[]);

            /**
             * The base address prefix can be empty
             */
            if (isset($core_settings['uri_prefix'])) {
                $config->set('uri_prefix', $core_settings['uri_prefix']);
            }
            /**
             * String settings in the config
             */
            foreach (['default_host', 'backend_prefix', 'assets_prefix'] as $v) {
                if (!empty($core_settings[$v])) {
                    $config->set($v, $core_settings[$v]);
                }
            }

            /**
             * Boolean settings in config
             */
            foreach (['debug', 'packages_settlements', 'symbiosis'] as $v) {
                if (isset($core_settings[$v])) {
                    $config->set($v, (bool)$core_settings[$v]);
                }
            }

            /**
             * Adding file storage from the settings
             * Binds names: assets_filesystem, media_filesystem, images_filesystem
             */
            $this->bindSettingsFilesystems($core, $core_settings);

            $core->instance(SettingsInterface::class, $core_settings);
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

    /**
     * Dynamic addition of file storages from settings
     *
     * @param DIContainerInterface $core
     * @param SettingsInterface    $settings
     *
     * assets_filesystem  - File storage for public application files (js, css, fonts, csv etc...)
     * media_filesystem   - Basic storage for media files (images, video, audio, etc...)
     * images_filesystem  - The storage for images is usually located inside the media folder.
     *                      Used by visual content editors.
     *
     * @info  The storages themselves are selected in the settings -> system application
     *
     * @return void
     */
    private function bindSettingsFilesystems(DIContainerInterface $core, SettingsInterface $settings): void
    {
        foreach (['assets', 'media', 'images'] as $filesystemName) {
            $key = $filesystemName . '_filesystem';
            if ($settings->has($key)) {
                $core->singleton($key, static function (CoreInterface $core) use ($filesystemName, $key) {
                    $disk = $core->get(SettingsInterface::class)->get($key);
                    if (empty($disk)) {
                        throw new FilesystemException(
                            'Kernel ' . ucfirst($filesystemName) . ' filesystem is not defined in settings!'
                        );
                    }
                    $filesystem = $core->get(FilesystemManagerInterface::class)->disk($disk);
                    if (!$filesystem) {
                        throw new FilesystemException(
                            'Kernel ' . ucfirst($filesystemName) . ' filesystem storage is not found!'
                        );
                    }
                    return $filesystem;
                });
            }
        }
    }
}