<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem;

interface CloudInterface
{
    /**
     * @param string $path
     *
     * @return string
     * @throws \Exception|FilesystemException
     */
    public function getUrl(string $path): string;
}
