<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem;


class Filesystem implements FilesystemInterface, CloudInterface
{


    /**
     * @param AdapterInterface $adapter
     * @param string|null      $baseUrl
     */
    public function __construct(protected AdapterInterface $adapter, protected string|null $baseUrl = null) {}

    /**
     * @inheritdoc
     */
    public function has(string $path): bool
    {
        $path = self::normalizePath($path);

        return !(strlen($path) === 0) && $this->getAdapter()->has($path);
    }

    public static function normalizePath(string $path): string
    {
        $path = rtrim(str_replace("\\", "/", trim($path)), '/');
        $unx = (strlen($path) > 0 && $path[0] == '/');
        $parts = array_filter(explode('/', $path));
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $path = implode('/', $absolutes);

        return $unx ? '/' . $path : $path;
    }

    /**
     * Get the Adapter.
     *
     * @return AdapterInterface adapter
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, $contents, array $options = []): bool
    {
        return $this->getAdapter()->write(self::normalizePath($path), $contents, $options);
    }

    /**
     * @param string $path
     *
     * @return string|false
     * @throws \Exception
     */
    public function readAndDelete(string $path): string|false
    {
        $path = self::normalizePath($path);
        $contents = $this->read($path);
        if ($contents === false) {
            return false;
        }
        $this->delete($path);
        return $contents;
    }

    public function getMTime(string $path): int|false
    {
        return $this->getAdapter()->getMTime(self::normalizePath($path));
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string|false
    {
        return $this->getAdapter()->read(self::normalizePath($path));
    }

    /**
     * @inheritdoc
     */
    public function delete(string $path): bool
    {
        return $this->getAdapter()->delete(self::normalizePath($path));
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newPath): bool
    {
        return $this->getAdapter()->rename(self::normalizePath($path), self::normalizePath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function copy(string $path, string $to): bool
    {
        return $this->getAdapter()->copy(self::normalizePath($path), self::normalizePath($to));
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path): bool
    {
        $dirname = self::normalizePath($path);

        if ($dirname === '') {
            throw new FilesystemException('Root directories can not be deleted.');
        }

        return $this->getAdapter()->deleteDir($dirname);
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $dirname, array $options = []): bool
    {
        return $this->getAdapter()->createDir(self::normalizePath($dirname), $options);
    }

    /**
     * @inheritdoc
     */
    public function listContents(string $directory = '', bool $recursive = false): array|false
    {
        return $this->getAdapter()->listContents(self::normalizePath($directory), $recursive);
    }

    /**
     * @inheritdoc
     */
    public function getMimetype(string $path): string|false
    {
        return $this->getAdapter()->getMimetype(self::normalizePath($path));
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp(string $path): int|false
    {
        return $this->getAdapter()->getTimestamp(self::normalizePath($path));
    }

    /**
     * @inheritdoc
     */
    public function getVisibility(string $path): string|false
    {
        return $this->getAdapter()->getVisibility($path);
    }

    /**
     * @inheritdoc
     */
    public function getSize(string $path): int|false
    {
        return $this->getAdapter()->getSize($path);
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility): bool
    {
        return $this->getAdapter()->setVisibility($path, $visibility);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(string $path): array|false
    {
        return $this->getAdapter()->getMetadata($path);
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws FilesystemException
     */
    public function getUrl(string $path): string
    {
        if ($this->adapter instanceof CloudInterface) {
            return $this->adapter->getUrl($path);
        } elseif ($this->adapter instanceof PathPrefixInterface) {
            if (empty($this->baseUrl)) {
                throw new FilesystemException("The base url is not defined!");
            }
            $basePath = $this->adapter->getPathPrefix();
            if (!empty($basePath)) {
                $path = preg_replace('/^' . preg_quote($basePath) . '/', '', $path);
            }
            return rtrim($this->baseUrl, '\\/') . '/' . ltrim($path, '\\/');
        }

        throw new FilesystemException("I can't create a url for the [$path] file!");
    }
}
