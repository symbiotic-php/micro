<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Psr\Container\ContainerInterface;
use Symbiotic\Container\CloningContainer;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Container\ServiceContainerInterface;


/**
 * Interface ApplicationInterface
 *
 * @property \Symbiotic\Core\CoreInterface|ApplicationInterface $app For plugins, the container will be the parent
 *           application
 *
 */
interface ApplicationInterface extends AppConfigInterface,
                                       DIContainerInterface,
                                       ServiceContainerInterface,
                                       CloningContainer
{


    /**
     * Returns the URL of the static application file
     *
     * @param string $path The path relative to the root of the application statics folder
     *
     * @return string
     */
    public function asset(string $path): string;

    /**
     * Returns the full path to the application assets folder
     *
     * @return string|null
     *
     * @deprecated
     * @uses \Symbiotic\Packages\AssetsRepositoryInterface::getAssetsPath()
     */
    public function getAssetsPath(): ?string;

    /**
     * Returns the full path to the application resources folder
     *
     * @return string|null
     *
     * @deprecated
     * @uses \Symbiotic\Packages\ResourcesRepositoryInterface::getResourcesPath()
     */
    public function getResourcesPath(): ?string;

    /**
     * Initial loading of the application and its providers
     *
     * @param \Closure[]|null $bootstraps Closures of child applications for bootstrap
     *
     * @return static
     */
    public function bootstrap(array $bootstraps = null): static;

    /**
     * Clones an application with a different root container
     *
     * Modes:
     * 1) If an application is passed as a container, it will be installed as the parent container.
     * 2) If the root container (Core Interface) is passed, the entire applications nesting chain will be cloned.
     *
     * Used in RoadRunner operation mode
     *
     * @param DIContainerInterface|null $container
     *
     * @return ApplicationInterface|null
     */
    public function cloneInstance(?ContainerInterface $container): ?object;

}