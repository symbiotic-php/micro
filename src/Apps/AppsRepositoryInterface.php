<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Psr\Container\ContainerInterface;
use Symbiotic\Container\CloningContainer;

interface AppsRepositoryInterface extends CloningContainer
{
    /**
     * Returns a list of enabled applications
     *
     * @return array
     *
     * @todo Need to move to the package repository probably
     */
    public function enabled(): array;

    /**
     * Getting the configuration array of all applications
     *
     * @return  array
     */
    public function all(): array;

    /**
     * Disabling applications by their ID
     *
     * @param array $ids
     *
     * @return void
     */
    public function disableApps(array $ids): void;

    /**
     * Getting all Application IDs
     *
     * @return string[]
     */
    public function getIds(): array;

    /**
     * Returns the application configuration object
     *
     * @param string $id
     *
     * @return AppConfigInterface|null
     */
    public function getConfig(string $id): ?AppConfigInterface;

    /**
     * Returns the application container
     *
     * @param string $id
     *
     * @return ApplicationInterface
     *
     * @throws AppNotFoundException If the application is not found, check through {@see has()}
     */
    public function get(string $id): ApplicationInterface;

    /**
     * Checking the availability of the application
     *
     * Important: Even a disabled application is an existing one
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Returns the application container with loaded services
     *
     * @param string $id
     *
     * @return ApplicationInterface
     *
     * @throws AppNotFoundException If the application is not found, check via {@see has()}
     */
    public function getBootedApp(string $id): ApplicationInterface;

    /**
     * Returns an array of application plugin IDs
     *
     * @param string $app_id
     *
     * @return array = ['app1','app2',....]
     */
    public function getPluginsIds(string $app_id): array;

    /**
     * Adding an application configuration
     *
     * (Can be used to create plugins for multiple applications)
     *
     * @param array $config
     *
     * @return void
     */
    public function addApp(array $config): void;

    /**
     * @param ContainerInterface|null $container
     *
     * @return AppsRepositoryInterface|null
     */
    public function cloneInstance(?ContainerInterface $container): ?AppsRepositoryInterface;

    /**
     * Clearing all application instances
     *
     * @return void
     */
    public function flush(): void;
}