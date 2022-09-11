<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Symbiotic\Container\ArrayContainerInterface;


interface AppConfigInterface extends ArrayContainerInterface
{
    /**
     * The app ID is based on its alias and parent ID
     * @return string
     *
     * @see \Symbiotic\Apps\AppsRepository::addApp();
     */
    public function getId(): string;

    /**
     * Returns the application name
     *
     * @return string
     */
    public function getAppName(): string;

    /**
     * Returns the full path to the package file or folder
     *
     * @param string|null $path The path relative to the root of the package
     *
     * @return string|null will be returned only when using the assembly in one file,
     * because all the code will be compiled in one script
     */
    public function getBasePath(string $path = null): ?string;

    /**
     * Returns the class name of the application Routing Provider
     *
     * @return string|null
     *
     * @see \Symbiotic\Routing\AppRoutingInterface
     */
    public function getRoutingProvider(): ?string;

    /**
     * Checking for the existence of a parent application
     *
     * @return bool
     */
    public function hasParentApp(): bool;

    /**
     * Returns the parent application ID, if any
     *
     * @return string|null
     */
    public function getParentAppId(): ?string;
}