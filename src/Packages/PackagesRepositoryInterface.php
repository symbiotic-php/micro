<?php

declare(strict_types=1);

namespace Symbiotic\Packages;


interface PackagesRepositoryInterface
{

    /**
     * Returns an array ids of all registered packages
     *
     * @return array = ['id1','id_2', ....]
     */
    public function getIds(): array;

    /* /!**
      * @return array
      *!/
     public function enabled(): array;*/


    /**
     * Checking the package availability
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Returns an array of package configuration
     *
     * @param string $id
     *
     * @return array
     *
     * @throws \Exception if not found, check with has() before calling
     */
    public function get(string $id): array;


    /**
     * Returns the package configuration object
     *
     * @param string $id
     *
     * @return PackageConfig|null
     */
    public function getPackageConfig(string $id): ?PackageConfig;

    /**
     * The method returns an array of configurations of all packages
     *
     * If you need an array of application IDs use {@see getIds()}
     * If you need to check the existence of a package use {@see has()}
     *
     * don't do that:
     *     $packages = $obj->all();
     *     if(isset($packages[$id])){}
     *
     * @return array
     */
    public function all(): array;

    /**
     * Adding a Package Loader
     *
     * @param PackagesLoaderInterface $loader
     */
    public function addPackagesLoader(PackagesLoaderInterface $loader): void;

    /**
     * Adding Package Configuration
     *
     * @param array $config
     *
     * @used-by PackagesLoaderInterface::load()
     * @see     PackagesLoaderFilesystem::load()
     *
     */
    public function addPackage(array $config): void;

    /**
     * Starting package collection
     *
     * @uses PackagesLoaderInterface::load()
     */
    public function load(): void;

    /**
     * Getting a list of kernel loader classes
     *
     * @return array
     */
    public function getBootstraps(): array;

    /**
     * Getting a list of event subscribers from packages
     *
     * @return array[] = ['\Events\EventClassName' => ['\My\Handler1','\Other\Handler3'], //....]
     */
    public function getEventsHandlers(): array;
}
