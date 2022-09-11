<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Psr\Http\Message\StreamInterface;


interface ResourcesRepositoryInterface
{

    /**
     * @param string $package_id
     * @param string $path
     *
     * @return StreamInterface
     * @throws \Throwable | ResourceExceptionInterface Если файл не найден
     */
    public function getResourceFileStream(string $package_id, string $path): StreamInterface;

    /**
     * @param string $package_id
     *
     * @return string|null  a string if the package folder is found
     */
    public function getResourcesPath(string $package_id): ?string;
}