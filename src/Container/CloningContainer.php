<?php

declare(strict_types=1);

namespace Symbiotic\Container;

use Psr\Container\ContainerInterface;

/**
 * Interface for managing the service when cloning a container and replicating it correctly
 */
interface CloningContainer
{
    /**
     * Service logic when cloning a DI Container
     *
     * @param ContainerInterface|null $container
     *
     * @return $this|object If you return the object, it will be overwritten in the container, you can also return
     *                      another wrapper object, just observe the interface
     * @return null         If it returns a service object, it will be overwritten in a new container
     */
    public function cloneInstance(?ContainerInterface $container): ?object;
}