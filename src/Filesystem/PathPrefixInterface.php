<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem;


interface PathPrefixInterface
{
    /**
     * Set base path
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPathPrefix(string $path): static;

    /**
     * Get base path
     *
     * @return string
     */
    public function getPathPrefix(): string;

    /**
     * Prepend base root to path
     *
     * @param string $path
     *
     * @return string
     */
    public function applyPathPrefix(string $path): string;

    /**
     * Delete root from path
     *
     * @param string $path
     *
     * @return string
     */
    public function removePathPrefix(string $path): string;
}
