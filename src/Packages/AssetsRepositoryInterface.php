<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Psr\Http\Message\StreamInterface;


interface AssetsRepositoryInterface
{
    /**
     * @param string $package_id
     * @param string $path
     *
     * @return StreamInterface
     * @throws \Throwable|ResourceExceptionInterface If the file is not found or is not readable
     */
    public function getAssetFileStream(string $package_id, string $path): StreamInterface;

    /**
     * Returns the full path to the package assets folder
     *
     * @param string $package_id
     *
     * @return string|null
     * @throws \Exception if package path not exists
     */
    public function getAssetsPath(string $package_id): ?string;
}