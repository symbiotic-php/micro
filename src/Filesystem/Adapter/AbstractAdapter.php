<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem\Adapter;

use Symbiotic\Filesystem\AdapterInterface;
use Symbiotic\Filesystem\PathPrefixInterface;

abstract class AbstractAdapter implements AdapterInterface, PathPrefixInterface
{

    protected string $path_prefix = '';

    public function applyPathPrefix(string $path): string
    {
        return empty($this->path_prefix) ? $path : $this->getPathPrefix() . ltrim($path, '\\/');
    }

    public function getPathPrefix(): string
    {
        return $this->path_prefix;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPathPrefix(string $path): static
    {
        if (!empty($path)) {
            $this->path_prefix = rtrim($path, '\\/') . '/';
        }

        return $this;
    }

    public function removePathPrefix(string $path): string
    {
        return str_replace($this->getPathPrefix(), '', $path);
    }

    public function normalizePath(string $path): string
    {
        $path = rtrim(str_replace("\\", "/", trim($path)), '/');
        $unx = (strlen($path) > 0 && $path[0] == '/');
        $parts = array_filter(explode('/', $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $path = implode('/', $absolutes);
        $path = $unx ? '/' . $path : $path;

        return $path;
    }
}
